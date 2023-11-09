import React, { useState, useEffect } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { injectGraphql, loadComponent } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import * as toastsActions from 'state/toasts/ToastsActions';
import backend from 'lib/Backend';
import Config from 'lib/Config';
import PropTypes from 'prop-types';

// section used in window.ss config
const section = 'SilverStripe\\LinkField\\Controllers\\LinkFieldController';

/**
 * value - ID of the Link passed from JsonField
 * onChange - callback function passed from JsonField - used to update the underlying <input> form field
 * types - injected by the GraphQL query
 * actions - object of redux actions
 */
const LinkField = ({ value, onChange, types, actions }) => {
  const linkID = value;
  const [typeKey, setTypeKey] = useState('');
  const [data, setData] = useState({});
  const [editing, setEditing] = useState(false);

  /**
   * Call back used by LinkModal after the form has been submitted and the response has been received
   */
  const onModalSubmit = async (modalData, action, submitFn) => {
    const formSchema = await submitFn();

    // slightly annoyingly, on validation error formSchema at this point will not have an errors node
    // instead it will have the original formSchema id used for the GET request to get the formSchema i.e.
    // admin/linkfield/schema/linkfield/<ItemID>
    // instead of the one used by the POST submission i.e.
    // admin/linkfield/linkForm/<LinkID>
    const hasValidationErrors = formSchema.id.match(/\/schema\/linkfield\/([0-9]+)/);
    if (!hasValidationErrors) {
      // get link id from formSchema response
      const match = formSchema.id.match(/\/linkForm\/([0-9]+)/);
      const valueFromSchemaResponse = parseInt(match[1], 10);

      // update component state
      setEditing(false);

      // update parent JsonField data id - this is required to update the underlying <input> form field
      // so that the Page (or other parent DataObject) gets the Link relation ID set
      onChange(valueFromSchemaResponse);

      // success toast
      actions.toasts.success('Saved link');
    }

    return Promise.resolve();
  };

  /**
   * Call back used by LinkPicker when the 'Clear' button is clicked
   */
  const onClear = () => {
    const endpoint = `${Config.getSection(section).form.linkForm.deleteUrl}/${linkID}`;
    // CSRF token 'X-SecurityID' headers needs to be present for destructive requests
    backend.delete(endpoint, {}, { 'X-SecurityID': Config.get('SecurityID') })
      .then(() => {
        actions.toasts.success('Deleted link');
      })
      .catch(() => {
        actions.toasts.error('Failed to delete link');
      });

    // update component state
    setTypeKey('');
    setData({});

    // update parent JsonField data ID used to update the underlying <input> form field
    onChange(0);
  };

  const title = data.Title || '';
  const type = types.hasOwnProperty(typeKey) ? types[typeKey] : {};
  const modalType = typeKey ? types[typeKey] : type;
  const handlerName = modalType && modalType.hasOwnProperty('handlerName')
    ? modalType.handlerName
    : 'FormBuilderModal';
  const LinkModal = loadComponent(`LinkModal.${handlerName}`);

  const pickerProps = {
    title,
    description: data.description,
    typeTitle: type.title || '',
    onEdit: () => {
      setEditing(true);
    },
    onClear,
    onSelect: (key) => {
      setTypeKey(key);
      setEditing(true);
    },
    types: Object.values(types)
  };

  const modalProps = {
    typeTitle: type.title || '',
    typeKey,
    editing,
    onSubmit: onModalSubmit,
    onClosed: () => {
      setEditing(false);
    },
    linkID
  };

  // read data from endpoint and update component state
  useEffect(() => {
    if (!editing && linkID) {
      const endpoint = `${Config.getSection(section).form.linkForm.dataUrl}/${linkID}`;
      backend.get(endpoint)
        .then(response => response.json())
        .then(responseJson => {
          setData(responseJson);
          setTypeKey(responseJson.typeKey);
        });
    }
  }, [editing, linkID]);

  return <>
    <LinkPicker {...pickerProps} />
    <LinkModal {...modalProps} />
  </>;
};

LinkField.propTypes = {
  value: PropTypes.number.isRequired,
  onChange: PropTypes.func.isRequired,
};

// redux actions loaded into props - used to get toast notifications
const mapDispatchToProps = (dispatch) => ({
  actions: {
    toasts: bindActionCreators(toastsActions, dispatch),
  },
});

export default compose(
  injectGraphql('readLinkTypes'),
  fieldHolder,
  connect(null, mapDispatchToProps)
)(LinkField);
