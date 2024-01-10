/* eslint-disable */
import React, { useState, useEffect, createContext } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { injectGraphql } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkPickerTitle from 'components/LinkPicker/LinkPickerTitle';
import Loading from 'components/Loading/Loading';
import LinkType from 'types/LinkType';
import LinkModalContainer from 'containers/LinkModalContainer';
import * as toastsActions from 'state/toasts/ToastsActions';
import backend from 'lib/Backend';
import Config from 'lib/Config';
import PropTypes from 'prop-types';
import i18n from 'i18n';
import url from 'url';
import qs from 'qs';

export const LinkFieldContext = createContext(null);

// section used in window.ss config
const section = 'SilverStripe\\LinkField\\Controllers\\LinkFieldController';

/**
 * value - ID of the Link passed from LinkField entwine
 * onChange - callback function passed from LinkField entwine - used to update the underlying <input> form field
 * types - types of the Link passed from LinkField entwine
 * actions - object of redux actions
 * isMulti - whether this field handles multiple links or not
 * canCreate - whether this field can create new links or not
 * ownerID - ID of the owner DataObject
 * ownerClass - class name of the owner DataObject
 * ownerRelation - name of the relation on the owner DataObject
 */
const LinkField = ({
  value = null,
  onChange,
  types = [],
  actions,
  isMulti = false,
  canCreate,
  ownerID,
  ownerClass,
  ownerRelation,
}) => {
  const [data, setData] = useState({});
  const [editingID, setEditingID] = useState(0);
  const [loading, setLoading] = useState(false);

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
      setLoading(true);
      const query = [];
      for (const linkID of linkIDs) {
        query.push(`itemIDs[]=${linkID}`);
      }
      const endpoint = `${Config.getSection(section).form.linkForm.dataUrl}?${query.join('&')}`;
      backend.get(endpoint)
        .then(response => response.json())
        .then(responseJson => {
          setData(responseJson);
          setLoading(false);
        })
        .catch(() => {
          actions.toasts.error(i18n._t('LinkField.FAILED_TO_LOAD_LINKS', 'Failed to load links'))
          setLoading(false);
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
      actions.toasts.success(i18n._t('LinkField.SAVE_SUCCESS', 'Saved link'));
  }

  /**
   * Update the component when the 'Delete' button in the LinkPicker is clicked
   */
  const onDelete = (linkID) => {
    let endpoint = `${Config.getSection(section).form.linkForm.deleteUrl}/${linkID}`;
    const parsedURL = url.parse(endpoint);
    const parsedQs = qs.parse(parsedURL.query);
    parsedQs.ownerID = ownerID;
    parsedQs.ownerClass = ownerClass;
    parsedQs.ownerRelation = ownerRelation;
    endpoint = url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
    const versionState = data[linkID]?.versionState || '';
    const isVersioned = ['draft', 'modified', 'published'].includes(versionState);
    const successText = isVersioned
      ? i18n._t('LinkField.ARCHIVE_SUCCESS', 'Archived link')
      : i18n._t('LinkField.DELETE_SUCCESS', 'Deleted link');
    const failedText = isVersioned
      ? i18n._t('LinkField.ARCHIVE_ERROR', 'Failed to archive link')
      : i18n._t('LinkField.DELETE_ERROR', 'Failed to delete link');
    // CSRF token 'X-SecurityID' headers needs to be present for destructive requests
    backend.delete(endpoint, {}, { 'X-SecurityID': Config.get('SecurityID') })
      .then(() => actions.toasts.success(successText))
      .catch(() => actions.toasts.error(failedText));

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
        versionState={data[linkID]?.versionState}
        typeTitle={type.title || ''}
        onDelete={onDelete}
        onClick={() => { setEditingID(linkID); }}
        canDelete={data[linkID]?.canDelete ? true : false}
      />);
    }
    return links;
  };

  const saveRecordFirst = ownerID === 0;
  const renderPicker = !saveRecordFirst && (isMulti || Object.keys(data).length === 0);
  const renderModal = !saveRecordFirst && Boolean(editingID);
  const saveRecordFirstText = i18n._t('LinkField.SAVE_RECORD_FIRST', 'Cannot add links until the record has been saved');

  if (loading && !saveRecordFirst) {
    return <div className="link-field__loading"><Loading/></div>;
  }

  return <LinkFieldContext.Provider value={{ ownerID, ownerClass, ownerRelation, actions }}>
    { saveRecordFirst && <div className="link-field__save-record-first">{saveRecordFirstText}</div>}
    { renderPicker && <LinkPicker
        onModalSuccess={onModalSuccess}
        onModalClosed={onModalClosed}
        types={types}
        canCreate={canCreate}
      /> }
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
  </LinkFieldContext.Provider>;
};

LinkField.propTypes = {
  value: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.number), PropTypes.number]),
  onChange: PropTypes.func.isRequired,
  types: PropTypes.array.isRequired,
  actions: PropTypes.object.isRequired,
  isMulti: PropTypes.bool,
  canCreate: PropTypes.bool.isRequired,
  ownerID: PropTypes.number.isRequired,
  ownerClass: PropTypes.string.isRequired,
  ownerRelation: PropTypes.string.isRequired,
};

// redux actions loaded into props - used to get toast notifications
const mapDispatchToProps = (dispatch) => ({
  actions: {
    toasts: bindActionCreators(toastsActions, dispatch),
  },
});

export { LinkField as Component };

export default compose(
  fieldHolder,
  connect(null, mapDispatchToProps)
)(LinkField);
