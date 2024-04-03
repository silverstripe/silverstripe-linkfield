/* eslint-disable */
import classnames from 'classnames';
import i18n from 'i18n';
import React, { useContext } from 'react';
import PropTypes from 'prop-types';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import { Button } from 'reactstrap';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import versionStates from 'constants/versionStates';

const stopPropagation = (fn) => (e) => {
  e.nativeEvent.stopImmediatePropagation();
  e.preventDefault();
  e.nativeEvent.preventDefault();
  e.stopPropagation();
  fn && fn();
}

const getVersionedBadge = (versionState) => {
  let title = '';
  let label = ''
  if (versionState === versionStates.draft) {
    title = i18n._t('LinkField.LINK_DRAFT_TITLE', 'Link has draft changes');
    label = i18n._t('LinkField.LINK_DRAFT_LABEL', 'Draft');
  } else if (versionState === versionStates.modified) {
    title = i18n._t('LinkField.LINK_MODIFIED_TITLE', 'Link has unpublished changes');
    label = i18n._t('LinkField.LINK_MODIFIED_LABEL', 'Modified');
  } else {
    return null;
  }
  const className = classnames('badge', `status-${versionState}`);
  return <span className={className} title={title}>{label}</span>;
};

const LinkPickerTitle = ({
  id,
  title,
  description,
  versionState,
  typeTitle,
  typeIcon,
  onDelete,
  onClick,
  onButtonKeyDownEdit,
  onUnpublishedVersionedState,
  canDelete,
  isMulti,
  isFirst,
  isLast,
  isSorting,
  canCreate,
  readonly,
  disabled,
  buttonRef,
}) => {
  const { loading } = useContext(LinkFieldContext);
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
  } = useSortable({id});

  const handleButtonKeyDown = (event) => {
    // Prevent the triggering the parent's keyboard sorting handler
    event.nativeEvent.stopImmediatePropagation();
    event.stopPropagation();
    if (['Enter', 'Space'].includes(event.code) && !loading) {
      onButtonKeyDownEdit(event);
    }
  };

  const handleDeleteKeyDown = (event) => {
    if (!['Enter', 'Space'].includes(event.code) || loading) {
      return;
    }
    event.nativeEvent.stopImmediatePropagation();
    event.stopPropagation();
    onDelete(id);
    event.nativeEvent.preventDefault();
    event.preventDefault();
  };

  const handleIconKeyDown = (event) => {
    if (!['Enter', 'Space'].includes(event.code)) {
      return;
    }
    const el = event.target;
    const newVal = el.getAttribute('aria-pressed') === 'true' ? 'false' : 'true';
    el.setAttribute('aria-pressed', newVal);
  };

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };
  const classes = {
    'link-picker__link': true,
    'link-picker__link--is-first': isFirst,
    'link-picker__link--is-last': isLast,
    'link-picker__link--is-sorting': isSorting,
    'form-control': true,
    'link-picker__link--readonly': readonly || !canCreate,
    'link-picker__link--disabled': disabled,
  };
  if (versionState) {
    classes[`link-picker__link--${versionState}`] = true;
  }
  const className = classnames(classes);
  const deleteText = [versionStates.unversioned, versionStates.unsaved].includes(versionState)
    ? i18n._t('LinkField.DELETE', 'Delete')
    : i18n._t('LinkField.ARCHIVE', 'Archive');
  const ariaLabel = i18n._t('LinkField.EDIT_LINK', 'Edit link');
  if ([versionStates.draft, versionStates.modified].includes(versionState)) {
    onUnpublishedVersionedState();
  }
  // Remove the default tabindex="0" attribute from the sortable element because we're going to manually
  // add this to the drag handle instead
  delete attributes.tabIndex;
  const idAttr = `link-picker__link-${id}`;
  const Tag = isMulti ? 'li' : 'div';
  return <Tag
    className={className}
    ref={setNodeRef}
    style={style}
    {...attributes}
    {...listeners}
    id={idAttr}
  >
    { (isMulti && !readonly && !disabled) && <div className="link-picker__drag-handle"
        tabIndex="0"
        role="button"
        aria-pressed="false"
        aria-controls={idAttr}
        aria-label="Sort Links"
        onKeyDown={handleIconKeyDown}
    >
      <i
        className="font-icon-drag-handle"
        aria-hidden="true"
        focusable="false"
      ></i>
    </div> }
    <Button
      aria-label={ariaLabel}
      disabled={loading}
      className={`link-picker__button ${typeIcon}`}
      color="secondary"
      onClick={stopPropagation(onClick)}
      innerRef={buttonRef}
      onKeyDown={handleButtonKeyDown}
    >
      <div className="link-picker__link-detail">
        <div className="link-picker__title">
          <span className="link-picker__title-text">{title}</span>
          {getVersionedBadge(versionState)}
        </div>
        {typeTitle && (
          <small className="link-picker__type">
            {typeTitle}:&nbsp;
            <span className="link-picker__url">{description}</span>
          </small>
        )}
      </div>
      {(canDelete && !readonly && !disabled) &&
        // This is a <span> rather than a <Button> because we're inside a <Button> and
        // trigger an error when you attempt to nest a <Button> inside a <Button>.
        <span
          aria-label={deleteText}
          role="button"
          tabIndex="0"
          className="link-picker__delete btn btn-link"
          onKeyDown={handleDeleteKeyDown}
          onClick={stopPropagation(() => !loading ? onDelete(id) : null)}
        >{deleteText}</span>
      }
    </Button>
  </Tag>
};

LinkPickerTitle.propTypes = {
  id: PropTypes.number.isRequired,
  title: PropTypes.string,
  description: PropTypes.string,
  versionState: PropTypes.oneOf(Object.values(versionStates)),
  typeTitle: PropTypes.string.isRequired,
  typeIcon: PropTypes.string.isRequired,
  onDelete: PropTypes.func.isRequired,
  onClick: PropTypes.func.isRequired,
  onButtonKeyDownEdit: PropTypes.func.isRequired,
  onUnpublishedVersionedState: PropTypes.func.isRequired,
  canDelete: PropTypes.bool.isRequired,
  isMulti: PropTypes.bool.isRequired,
  isFirst: PropTypes.bool.isRequired,
  isLast: PropTypes.bool.isRequired,
  isSorting: PropTypes.bool.isRequired,
  canCreate: PropTypes.bool.isRequired,
  readonly: PropTypes.bool.isRequired,
  disabled: PropTypes.bool.isRequired,
  buttonRef: PropTypes.object.isRequired,
};

LinkPickerTitle.defaultProps = {
  versionState: versionStates.unversioned,
}

export default LinkPickerTitle;
