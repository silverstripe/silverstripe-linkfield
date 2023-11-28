/* eslint-disable */
import React, { useState, useEffect } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { injectGraphql } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkPickerTitle from 'components/LinkPicker/LinkPickerTitle';
import LinkType from 'types/LinkType';
import LinkModalContainer from 'containers/LinkModalContainer';
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
 * isMulti - whether this field handles multiple links or not
 */
const LinkField = ({ value = null, onChange, types, actions, isMulti = false  }) => {
  const [data, setData] = useState({});
  const [editingID, setEditingID] = useState(0);

  // Ensure we have a valid array
  let linkIDs = value;
  if (!Array.isArray(linkIDs)) {
    if (typeof linkIDs === 'number' && linkIDs != 0) {
      linkIDs = [linkIDs];
    }
    if (!linkIDs) {
      linkIDs = [];
    }
  }

  // Read data from endpoint and update component state
  // This happens any time a link is added or removed and triggers a re-render
  useEffect(() => {
    if (!editingID && linkIDs.length > 0) {
      const query = [];
      for (const linkID of linkIDs) {
        query.push(`itemIDs[]=${linkID}`);
      }
      const endpoint = `${Config.getSection(section).form.linkForm.dataUrl}?${query.join('&')}`;
      backend.get(endpoint)
        .then(response => response.json())
        .then(responseJson => {
          setData(responseJson);
        });
    }
  }, [editingID, value && value.length]);

  /**
   * Unset the editing ID when the editing modal is closed
   */
  const onModalClosed = () => {
    setEditingID(0);
  };

  /**
   * Update the component when the modal successfully saves a link
   */
  const onModalSuccess = (value) => {
      // update component state
      setEditingID(0);

      const ids = [...linkIDs];
      if (!ids.includes(value)) {
        ids.push(value);
      }

      // Update value in the underlying <input> form field
      // so that the Page (or other parent DataObject) gets the Link relation set.
      // Also likely required in react context for dirty form state, etc.
      onChange(isMulti ? ids : ids[0]);

      // success toast
      actions.toasts.success(
        i18n._t(
          'LinkField.SAVE_SUCCESS',
          'Saved link',
        )
      );
  }

  /**
   * Update the component when the 'Clear' button in the LinkPicker is clicked
   */
  const onClear = (linkID) => {
    const endpoint = `${Config.getSection(section).form.linkForm.deleteUrl}/${linkID}`;
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
    const newData = {...data};
    delete newData[linkID];
    setData(newData);

    // update parent JsonField data IDs used to update the underlying <input> form field
    onChange(isMulti ? Object.keys(newData) : 0);
  };

  /**
   * Render all of the links currently in the field data
   */
  const renderLinks = () => {
    const links = [];

    for (const linkID of linkIDs) {
      // Only render items we have data for
      const linkData = data[linkID];
      if (!linkData) {
        continue;
      }

      const type = types.hasOwnProperty(data[linkID]?.typeKey) ? types[data[linkID]?.typeKey] : {};
      links.push(<LinkPickerTitle
        key={linkID}
        id={linkID}
        title={data[linkID]?.Title}
        description={data[linkID]?.description}
        typeTitle={type.title || ''}
        onClear={onClear}
        onClick={() => { setEditingID(linkID); }}
      />);
    }
    return links;
  };

  const renderPicker = isMulti || Object.keys(data).length === 0;
  const renderModal = Boolean(editingID);

  return <>
    { renderPicker && <LinkPicker onModalSuccess={onModalSuccess} onModalClosed={onModalClosed} types={types} /> }
    <div> { renderLinks() } </div>
    { renderModal && <LinkModalContainer
        types={types}
        typeKey={data[editingID]?.typeKey}
        isOpen={Boolean(editingID)}
        onSuccess={onModalSuccess}
        onClosed={onModalClosed}
        linkID={editingID}
      />
    }
  </>;
};

LinkField.propTypes = {
  value: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.number), PropTypes.number]),
  onChange: PropTypes.func.isRequired,
  types: PropTypes.objectOf(LinkType).isRequired,
  actions: PropTypes.object.isRequired,
  isMulti: PropTypes.bool,
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
