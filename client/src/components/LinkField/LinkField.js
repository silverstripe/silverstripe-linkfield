/* eslint-disable */
import React, { useState, useEffect, useRef, createContext } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import {
  closestCenter,
  DndContext,
  KeyboardCode,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors
} from '@dnd-kit/core';
import { arrayMove, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { restrictToVerticalAxis, restrictToParentElement } from '@dnd-kit/modifiers';
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
import versionStates from 'constants/versionStates';

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
 * inHistoryViewer - if the field is being viewed in the context of the history viewer
 */
const LinkField = ({
  value = null,
  onChange = () => {},
  types = {},
  actions,
  isMulti = false,
  canCreate,
  readonly,
  disabled,
  ownerID,
  ownerClass,
  ownerRelation,
  excludeLinkTextField = false,
  inHistoryViewer,
}) => {
  const [data, setData] = useState({});
  const [editingID, setEditingID] = useState(0);
  const [focusOnID, setFocusOnID] = useState(0);
  const [focusOnNewLinkWhenClosed, setFocusOnNewLinkWhenClosed] = useState(false);
  const [focusOnNewLink, setFocusOnNewLink] = useState(false);
  const [focusOnIDWhenAvailable, setFocusOnIDWhenAvailable] = useState(0);
  const [focusOnLinkPicker, setFocusOnLinkPicker] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingError, setLoadingError] = useState(false);
  const [forceFetch, setForceFetch] = useState(0);
  const [isSorting, setIsSorting] = useState(false);
  const [linksClassName, setLinksClassName] = useState(classnames({'link-picker-links': true}));

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 10
      }
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: (event, args) => {
        event.preventDefault();
        const { active, over, droppableContainers } = args.context;
        if (!active || !active.data || !active.data.current) {
          return;
        }
        const items = active.data.current.sortable.items;
        const overId = over ? over.id : active.id;
        const overIndex = items.indexOf(overId);
        const activeIndex = items.indexOf(active.id);
        const directionUp = -1;
        const directionDown = 1;
        let nextIndex = overIndex;
        let direction = directionDown;
        switch (event.code) {
          case KeyboardCode.Down:
          case KeyboardCode.Right:
            nextIndex = Math.min(overIndex + 1, items.length - 1);
            break;
          case KeyboardCode.Up:
          case KeyboardCode.Left:
            nextIndex = Math.max(0, overIndex - 1);
            direction = directionUp;
            break;
          default:
            return;
        }
        if (overIndex === nextIndex) {
          return;
        }
        const sortedItems = arrayMove(items, activeIndex, overIndex);
        const currentNodeIdAtNextIndex = sortedItems[nextIndex];
        if (!droppableContainers.has(currentNodeIdAtNextIndex)) {
          return;
        }
        const activeNode = droppableContainers.get(active.id).node.current;
        if (!droppableContainers.has(active.id)) {
          return;
        }
        const newNode = droppableContainers.get(currentNodeIdAtNextIndex).node.current;
        const activeRect = activeNode.getBoundingClientRect();
        const newRect = newNode.getBoundingClientRect();
        const offset = direction === directionDown
          ? newRect.top - activeRect.bottom
          : activeRect.top - newRect.bottom;
        return {
          x: 0,
          y: activeRect.top + direction * (newRect.height + offset),
        };
      }
    })
  );

  // Sometimes, the value will be given as a numeric string.
  // Ensure that an actual number is used
  if (typeof value === 'string') {
    value = Number(value);
  }

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
        })
        .catch(() => {
          setLoadingError(true);
        })
        .finally(() => {
          setLoading(false);
          // isSorting is set to true on drag start and only set to false here to prevent
          // the loading indicator for flickering
          setIsSorting(false);
        })
    }
  }, [editingID, value && value.length, forceFetch]);

  // Create a ref for the LinkPicker so it can be focused
  const linkPickerRef = useRef(null);

  // Create refs for each LinkPickerTitle button so they can be focused
  let refCount = 0;
  const linkButtonRefs = []
  for (const linkID of linkIDs) {
    linkButtonRefs[linkID] = useRef(null);
    refCount++;
  }
  // Ensure the exact same number of hooks are called on every render
  // If this this isn't done then a react error will be thrown when a link is deleted
  while (refCount < 256) {
    useRef(null);
    refCount++;
  }

  // This sets focus after closed the modal when creating a new link with the single-linkfield
  useEffect(() => {
    if (!focusOnNewLink || loading) {
      return;
    }
    if (linkIDs.length === 0) {
      // User opened modal but exited without of saving
      setFocusOnLinkPicker(true);
    } else {
      // User opened modal and created a new link
      const linkID = linkIDs[0];
      if (linkButtonRefs[linkID].current) {
        setFocusOnIDWhenAvailable(linkID);
      }
      setFocusOnNewLink(false);
    }
  }, [focusOnNewLink, loading, linkIDs]);

  // This sets focus after closing a modal for both single-linkfield and multi-linkfield
  // when editing an existing link
  // Note setFocusOnIDWhenAvailable is used because the .focus() must not be called immediately
  // if we try to focus on the link at this point then it will immediately lose focus
  useEffect(() => {
    if (!focusOnID || loading) {
      return;
    }
    setFocusOnIDWhenAvailable(focusOnID);
    setFocusOnID(0);
  }, [focusOnID, loading]);

  // Focus on the a link when it's ready for focus
  useEffect(() => {
    if (focusOnIDWhenAvailable === 0 || loading) {
      return;
    }
    linkButtonRefs[focusOnIDWhenAvailable].current.focus();
    setFocusOnIDWhenAvailable(0);
  }, [focusOnIDWhenAvailable, loading]);

  // Focus on the link picker when it's available for focus
  // The use of a useEffect block isn't strictly needed in all scenarios when focusing on the
  // link picker, however it is needed when archiving a single-linkfield.link
  // For the sake of consistency, only use `focusOnLinkPicker(true)` to focus on the link picker
  useEffect(() => {
    if (!focusOnLinkPicker || !linkPickerRef.current || loading) {
      return;
    }
    linkPickerRef.current.focus();
    setFocusOnLinkPicker(false);
  }, [focusOnLinkPicker, loading, linkPickerRef]);

  /**
   * Unset the editing ID when the editing modal is closed
   * Focus on button used to open the modal
   */
  const handleModalClosed = () => {
    if (editingID) {
      setFocusOnID(editingID);
    } else if (focusOnNewLinkWhenClosed) {
      // This is called when a user uses single-linkfield to create a new link and then
      // successfully saves the link
      // Closing the a single-linkfield modal without saving is handled elsewhere
      setFocusOnNewLink(true);
    } else {
      // This is called when a user uses multi-linkfield and either sucessfully save a new link
      // or closes the modal without saving
      setFocusOnLinkPicker(true);
    }
    setEditingID(0);
    setFocusOnNewLinkWhenClosed(false);
  };

  /**
   * Update the component when the modal successfully saves a link
   */
  const handleModalSuccess = (value) => {
      handleModalClosed();
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

  const handleSelectType = () => {
    if (!isMulti) {
      setFocusOnNewLinkWhenClosed(true);
    }
  }

  /**
   * Update the component when the 'Delete' button in the LinkPicker is clicked
   */
  const handleDelete = (linkID, deleteType) => {
    const versionState = data[linkID]?.versionState || '';
    const isVersioned = [
      versionStates.draft,
      versionStates.modified,
      versionStates.published
    ].includes(versionState);
    const deleteText = isVersioned
      ? i18n._t('LinkField.ARCHIVE_CONFIRM', 'Are you sure you want to archive this link?')
      : i18n._t('LinkField.DELETE_CONFIRM', 'Are you sure you want to delete this link?');
    if (!window.confirm(deleteText)) {
      return false;
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

    // Work out where to put focus after delete
    // First create an array of linkIDs sorted by sort order
    // Note - key is a linkID
    const keysObj = {};
    const keys = [];
    for (const key in data) {
      const sort = Number(data[key].sort);
      keysObj[sort] = key;
    }
    const sorts = Object.keys(keysObj).sort((a, b) => a - b);
    for (const sort of sorts) {
      keys[keys.length] = Number(keysObj[sort]);
    }

    const index = keys.indexOf(linkID);
    const isOnlyLink = keys.length === 1;
    const isLastLink = index === keys.length - 1;
    if (isOnlyLink) {
      // If link was the only one then put focus on the picker
      setFocusOnLinkPicker(true);
    } else {
      // If more than one link ...
      if (isLastLink) {
        // and link was last then focus on previous link
        setFocusOnID(keys[index - 1]);
      } else {
        // and link was not last link then focus on next link
        setFocusOnID(keys[index + 1]);
      }
    }

    // Update component state
    const newData = {...data};
    delete newData[linkID];
    setData(newData);

    // Update parent JsonField data IDs used to update the underlying <input> form field
    // Not using Object.keys() to ensure that int key sort order is retained
    const newLinkIDs = [];
    for (const id of linkIDs) {
      if (id !== linkID) {
        newLinkIDs.push(id);
      }
    }
    onChange(isMulti ? newLinkIDs : 0);
  };

  /**
   * Update the edit form "Publish" button state to be dirty when an link has an
   * unpublished version state
   *
   * We do not update the state of the "Save" button because LinkField exclusively updates
   * via AJAX so that there's no need to save the page to update a Link DataObject
   *
   * This is fairly hackish code that directly manipulates the DOM, however there's no
   * clean way to do this since the publish button is not a react component, and the existing
   * jQuery change tracker does not allow independently updating only the publish button
   */
  const handleUnpublishedVersionedState = () => {
    const cssSelector = [
      // CMS page edit form publish button
      '.cms-edit-form button[data-text-alternate]#Form_EditForm_action_publish',
      // GridField managed DataObject edit form publish button
      '.cms-edit-form button[data-text-alternate]#Form_ItemEditForm_action_doPublish'
    ].join(',');
    const publishButton = document.querySelector(cssSelector);
    if (!publishButton) {
      return;
    }
    const dataBtnAlternateRemove = publishButton.getAttribute('data-btn-alternate-remove') || '';
    dataBtnAlternateRemove.split(' ').forEach((className) => {
      if (className) {
        publishButton.classList.remove(className);
      }
    });
    const dataBtnAlternateAdd = publishButton.getAttribute('data-btn-alternate-add') || '';
    dataBtnAlternateAdd.split(' ').forEach((className) => {
      if (className) {
        publishButton.classList.add(className);
      }
    });
    const dataTextAlternate = publishButton.getAttribute('data-text-alternate');
    if (dataTextAlternate) {
      publishButton.innerHTML = dataTextAlternate;
    }
  }

  /**
   * Render all of the links currently in the field data
   */
  const renderLinks = () => {
    const links = [];
    for (let i = 0; i < linkIDs.length; i++) {
      const linkID = linkIDs[i];
      // Render dataless item to provide a good loading experience, except if we have single link field
      const linkData = data[linkID] || {};
      if (!linkData && !isMulti) {
        continue;
      }
      const type = types.hasOwnProperty(linkData.typeKey) ?
        types[linkData.typeKey] :
        {icon: 'font-icon-link' };
      links.push(<LinkPickerTitle
        key={linkID}
        id={linkID}
        title={linkData.title}
        description={linkData.description}
        versionState={linkData.versionState}
        statusFlags={linkData.statusFlags}
        typeTitle={type.title || ''}
        typeIcon={type.icon}
        onDelete={handleDelete}
        onClick={() => { setEditingID(linkID); }}
        onUnpublishedVersionedState={handleUnpublishedVersionedState}
        canDelete={linkData.canDelete ? true : false}
        isMulti={isMulti}
        isFirst={i === 0}
        isLast={i === linkIDs.length - 1}
        isSorting={isSorting}
        canCreate={canCreate}
        readonly={readonly}
        disabled={disabled}
        buttonRef={linkButtonRefs[linkID]}
      />);
    }
    return links;
  };

  const sortableLinks = () => {
    if (isMulti && !readonly && !disabled) {
      return <div className={linksClassName} onBlur={() => setIsSorting(false)}>
        <DndContext modifiers={[restrictToVerticalAxis, restrictToParentElement]}
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragStart={handleDragStart}
          onDragEnd={handleDragEnd}
        >
          <ol className="link-picker__list">
            <SortableContext
              items={linkIDs}
              strategy={verticalListSortingStrategy}
            >
              {links}
            </SortableContext>
          </ol>
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
        // Force a rerender so that links are retched so that status flag badges are up to date
        setForceFetch(forceFetch + 1);
      })
      .catch(() => {
        actions.toasts.error(i18n._t('LinkField.SORT_ERROR', 'Failed to sort links'));
      });
  }

  const saveRecordFirst = !loadingError && ownerID === 0;
  const renderLoadingError = loadingError;
  const renderPicker = !loadingError && !inHistoryViewer && !saveRecordFirst && (isMulti || linkIDs.length === 0);
  const renderModal = !loadingError && !saveRecordFirst && Boolean(editingID);
  const loadingErrorText = i18n._t('LinkField.FAILED_TO_LOAD_LINKS', 'Failed to load link(s)');
  const saveRecordFirstText = i18n._t('LinkField.SAVE_RECORD_FIRST', 'Cannot add links until the record has been saved');
  const links = renderLinks();

  return <LinkFieldContext.Provider value={{
    ownerID,
    ownerClass,
    ownerRelation,
    actions,
    loading,
    excludeLinkTextField,
    inHistoryViewer
  }}>
    <div className="link-field__container">
      { renderLoadingError && <div className="link-field__loading-error">{loadingErrorText}</div> }
      { saveRecordFirst && <div className="link-field__save-record-first">{saveRecordFirstText}</div>}
      { loading && !isSorting && !saveRecordFirst && <Loading containerClass="link-field__loading"/> }
      { renderPicker && <LinkPicker
          onModalSuccess={handleModalSuccess}
          onModalClosed={handleModalClosed}
          types={types}
          canCreate={canCreate}
          readonly={readonly}
          disabled={disabled}
          onSelectType={handleSelectType}
          dropdownToggleRef={linkPickerRef}
        /> }
      {sortableLinks()}
      { /* This <LinkModalContainer> is only used for editing EXISTING links */ }
      { renderModal && <LinkModalContainer
          types={types}
          typeKey={data[editingID]?.typeKey}
          isOpen={Boolean(editingID)}
          onSuccess={handleModalSuccess}
          onClosed={handleModalClosed}
          linkID={editingID}
        />
      }
    </div>
  </LinkFieldContext.Provider>;
};

LinkField.propTypes = {
  value: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.number), PropTypes.number, PropTypes.string]),
  onChange: PropTypes.func,
  types: PropTypes.object.isRequired,
  actions: PropTypes.object.isRequired,
  isMulti: PropTypes.bool,
  canCreate: PropTypes.bool.isRequired,
  readonly: PropTypes.bool.isRequired,
  disabled: PropTypes.bool.isRequired,
  ownerID: PropTypes.number.isRequired,
  ownerClass: PropTypes.string.isRequired,
  ownerRelation: PropTypes.string.isRequired,
  excludeLinkTextField: PropTypes.bool,
  inHistoryViewer: PropTypes.bool,
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
