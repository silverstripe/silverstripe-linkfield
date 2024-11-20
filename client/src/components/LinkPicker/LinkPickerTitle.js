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

const renderStatusFlagBadges = (statusFlags) => {
  if (!statusFlags) {
    return null;
  }
  const badges = [];
  for (let [cssClasses, data] of Object.entries(statusFlags)) {
    cssClasses = `badge status-${cssClasses}`;
    if (typeof data === 'string') {
      data = {text: data};
    }
    if (!data.title) {
      data.title = '';
    }
    badges.push(<span key={cssClasses} className={cssClasses} title={data.title}>{data.text}</span>);
  }
  return badges;
};

const LinkPickerTitle = ({
  id,
  title,
  description,
  versionState,
  statusFlags,
  typeTitle,
  typeIcon,
  onDelete,
  onClick,
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

  // dndkit will not set aria-pressed to undefined by default meaning it won't be an
  // html attribute. it's a little weird so set it to default to false if it's not set
  if (!attributes.hasOwnProperty('aria-pressed')) {
    attributes['aria-pressed'] = false;
  }

  const idAttr = `link-picker__link-${id}`;
  const Tag = isMulti ? 'li' : 'div';
  return <Tag
    className={className}
    ref={setNodeRef}
    style={style}
    {...listeners}
    id={idAttr}
    aria-disabled={readonly || disabled}
  >
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
          {renderStatusFlagBadges(statusFlags)}
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
    { (isMulti && !readonly && !disabled) && <div className="link-picker__drag-handle"
        {...attributes}
        tabIndex="0"
        role="button"
        aria-controls={idAttr}
        aria-label="Sort Links"
    >
      <i
        className="font-icon-drag-handle"
        aria-hidden="true"
        focusable="false"
      ></i>
    </div> }
  </Tag>
};

LinkPickerTitle.propTypes = {
  id: PropTypes.number.isRequired,
  title: PropTypes.string,
  description: PropTypes.string,
  versionState: PropTypes.oneOf(Object.values(versionStates)),
  statusFlags: PropTypes.object,
  typeTitle: PropTypes.string.isRequired,
  typeIcon: PropTypes.string.isRequired,
  onDelete: PropTypes.func.isRequired,
  onClick: PropTypes.func.isRequired,
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
