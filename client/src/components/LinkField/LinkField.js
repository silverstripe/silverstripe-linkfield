/* eslint-disable */
import React, { useState, useEffect, createContext } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { DndContext, closestCenter, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { restrictToVerticalAxis, restrictToParentElement } from '@dnd-kit/modifiers';
import { injectGraphql } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkPickerTitle from 'components/LinkPicker/LinkPickerTitle';
import Loading from 'components/Loading/Loading';
import LinkModalContainer from 'containers/LinkModalContainer';
import * as toastsActions from 'state/toasts/ToastsActions';
import backend from 'lib/Backend';
import Config from 'lib/Config';
import { joinUrlPaths } from 'lib/urls';
import PropTypes from 'prop-types';
import i18n from 'i18n';
import url from 'url';
import qs from 'qs';
import classnames from 'classnames';

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
 * readonly - whether this field is readonly or not
 * disabled - whether this field is disabled or not
 * ownerID - ID of the owner DataObject
 * ownerClass - class name of the owner DataObject
 * ownerRelation - name of the relation on the owner DataObject
 */
const LinkField = ({
  value = null,
  onChange,
  types = {},
  actions,
  isMulti = false,
  canCreate,
  readonly,
  disabled,
  ownerID,
  ownerClass,
  ownerRelation,
}) => {
  const [data, setData] = useState({});
  const [editingID, setEditingID] = useState(0);
  const [loading, setLoading] = useState(false);
  const [forceFetch, setForceFetch] = useState(0);
  const [isSorting, setIsSorting] = useState(false);
  const [linksClassName, setLinksClassName] = useState(classnames({'link-picker-links': true}));

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 10
      }
    })
  );

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
          // isSorting is set to true on drag start and only set to false here to prevent
          // the loading indicator for flickering
          setIsSorting(false);
        })
        .catch(() => {
          actions.toasts.error(i18n._t('LinkField.FAILED_TO_LOAD_LINKS', 'Failed to load links'))
          setLoading(false);
          setIsSorting(false);
        });
    }
  }, [editingID, value && value.length, forceFetch]);

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
  const onDelete = (linkID, deleteType) => {
    const versionState = data[linkID]?.versionState || '';
    const isVersioned = ['draft', 'modified', 'published'].includes(versionState);
    const deleteText = isVersioned
      ? i18n._t('LinkField.ARCHIVE_CONFIRM', 'Are you sure you want to archive this link?')
      : i18n._t('LinkField.DELETE_CONFIRM', 'Are you sure you want to delete this link?');
    if (!window.confirm(deleteText)) {
      return;
    }
    let endpoint = joinUrlPaths(Config.getSection(section).form.linkForm.deleteUrl, linkID.toString());
    const parsedURL = url.parse(endpoint);
    const parsedQs = qs.parse(parsedURL.query);
    parsedQs.ownerID = ownerID;
    parsedQs.ownerClass = ownerClass;
    parsedQs.ownerRelation = ownerRelation;
    endpoint = url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
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

    for (let i = 0; i < linkIDs.length; i++) {
      const linkID = linkIDs[i];
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
        typeIcon={type.icon}
        onDelete={onDelete}
        onClick={() => { setEditingID(linkID); }}
        canDelete={data[linkID]?.canDelete ? true : false}
        isMulti={isMulti}
        isFirst={i === 0}
        isLast={i === linkIDs.length - 1}
        isSorting={isSorting}
        canCreate={canCreate}
        readonly={readonly}
        disabled={disabled}
      />);
    }
    return links;
  };

  const sortableLinks = () => {
    if (isMulti && !readonly && !disabled) {
      return <div className={linksClassName}>
        <DndContext modifiers={[restrictToVerticalAxis, restrictToParentElement]}
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragStart={handleDragStart}
          onDragEnd={handleDragEnd}
        >
          <SortableContext 
            items={linkIDs}
            strategy={verticalListSortingStrategy}
          >
            {links}
          </SortableContext>
        </DndContext>
      </div> 
    }
    return <div>{links}</div>
  };

  const handleDragStart = (event) => {
    setLinksClassName(classnames({
      'link-picker__links': true,
      'link-picker__links--dragging': true,
    }));
    setIsSorting(true);
  }

  /**
   * Drag and drop handler for MultiLinkField's
   */
  const handleDragEnd = (event) => {
    const {active, over} = event;
    setLinksClassName(classnames({
      'link-picker__links': true,
      'link-picker__links--dragging': false,
    }));
    if (active.id === over.id) {
      setIsSorting(false);
      return;
    }
    // Update the local entwine state via onChange so that sorting looks correct on the frontend
    // and make a request to the server to update the database
    // Note that setIsSorting is not set to true here, instead it's set in the useEffect() block
    // higher up in this file
    const fromIndex = linkIDs.indexOf(active.id);
    const toIndex = linkIDs.indexOf(over.id);
    const newLinkIDs = arrayMove(linkIDs, fromIndex, toIndex);
    onChange(newLinkIDs);
    let endpoint = `${Config.getSection(section).form.linkForm.sortUrl}`;
    // CSRF token 'X-SecurityID' headers needs to be present
    backend.post(endpoint, { newLinkIDs }, { 'X-SecurityID': Config.get('SecurityID') })
      .then(async () => {
        onChange(newLinkIDs);
        actions.toasts.success(i18n._t('LinkField.SORT_SUCCESS', 'Updated link sort order'));
        // Force a rerender so that links are retched so that versionState badges are up to date
        setForceFetch(forceFetch + 1);
      })
      .catch(() => {
        actions.toasts.error(i18n._t('LinkField.SORT_ERROR', 'Failed to sort links'));
      });
  }

  const saveRecordFirst = ownerID === 0;
  const renderPicker = !saveRecordFirst && (isMulti || Object.keys(data).length === 0);
  const renderModal = !saveRecordFirst && Boolean(editingID);
  const saveRecordFirstText = i18n._t('LinkField.SAVE_RECORD_FIRST', 'Cannot add links until the record has been saved');
  const links = renderLinks();

  return <LinkFieldContext.Provider value={{ ownerID, ownerClass, ownerRelation, actions, loading }}>
    <div className="link-field__container">
      { saveRecordFirst && <div className="link-field__save-record-first">{saveRecordFirstText}</div>}
      { loading && !isSorting && !saveRecordFirst && <Loading containerClass="link-field__loading"/> }
      { renderPicker && <LinkPicker
          onModalSuccess={onModalSuccess}
          onModalClosed={onModalClosed}
          types={types}
          canCreate={canCreate}
          readonly={readonly}
          disabled={disabled}
        /> }
      {sortableLinks()}
      { renderModal && <LinkModalContainer
          types={types}
          typeKey={data[editingID]?.typeKey}
          isOpen={Boolean(editingID)}
          onSuccess={onModalSuccess}
          onClosed={onModalClosed}
          linkID={editingID}
        />
      }
    </div>
  </LinkFieldContext.Provider>;
};

LinkField.propTypes = {
  value: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.number), PropTypes.number]),
  onChange: PropTypes.func.isRequired,
  types: PropTypes.object.isRequired,
  actions: PropTypes.object.isRequired,
  isMulti: PropTypes.bool,
  canCreate: PropTypes.bool.isRequired,
  readonly: PropTypes.bool.isRequired,
  disabled: PropTypes.bool.isRequired,
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
