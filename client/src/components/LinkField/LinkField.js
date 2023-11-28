/* eslint-disable */
import React, { useState, useEffect } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { injectGraphql, loadComponent } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkPickerTitle from 'components/LinkPicker/LinkPickerTitle';
import * as toastsActions from 'state/toasts/ToastsActions';
import backend from 'lib/Backend';
import Config from 'lib/Config';
import PropTypes from 'prop-types';
import i18n from 'i18n';

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
  const [data, setData] = useState({});
  const [editing, setEditing] = useState(false);

  const onModalClosed = () => {
    setEditing(false);
  };

  const onModalSuccess = (value) => {
      // update component state
      setEditing(false);

      // update parent JsonField data id - this is required to update the underlying <input> form field
      // so that the Page (or other parent DataObject) gets the Link relation ID set
      onChange(value);

      // success toast
      actions.toasts.success(
        i18n._t(
          'LinkField.SAVE_SUCCESS',
          'Saved link',
        )
      );
  }

  /**
   * Call back used by LinkPicker when the 'Clear' button is clicked
   */
  const onClear = (id) => {
    const endpoint = `${Config.getSection(section).form.linkForm.deleteUrl}/${id}`;
    // CSRF token 'X-SecurityID' headers needs to be present for destructive requests
    backend.delete(endpoint, {}, { 'X-SecurityID': Config.get('SecurityID') })
      .then(() => {
        actions.toasts.success(
          i18n._t(
            'LinkField.DELETE_SUCCESS',
            'Deleted link',
          )
        );
      })
      .catch(() => {
        actions.toasts.error(
          i18n._t(
            'LinkField.DELETE_ERROR',
            'Failed to delete link',
          )
        );
      });

    // update component state
    setData({});

    // update parent JsonField data ID used to update the underlying <input> form field
    onChange(0);
  };

  const title = data.Title || '';
  const type = types.hasOwnProperty(data.typeKey) ? types[data.typeKey] : {};
  const handlerName = type && type.hasOwnProperty('handlerName')
    ? type.handlerName
    : 'FormBuilderModal';
  const LinkModal = loadComponent(`LinkModal.${handlerName}`);

  const pickerProps = {
    onModalSuccess,
    onModalClosed,
    types
  };

  const modalProps = {
    typeTitle: type.title || '',
    typeKey: data.typeKey,
    isOpen: editing,
    onSuccess: onModalSuccess,
    onClosed: onModalClosed,
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
        });
    }
  }, [editing, linkID]);

  return <>
    {!type.title && <LinkPicker {...pickerProps} />}
    {type.title && <LinkPickerTitle
      id={linkID}
      title={title}
      description={data.description}
      typeTitle={type.title}
      onClear={onClear}
      onClick={() => { setEditing(true); }}
    />}
    { editing && <LinkModal {...modalProps} /> }
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
