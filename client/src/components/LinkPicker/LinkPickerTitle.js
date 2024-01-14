/* eslint-disable */
import classnames from 'classnames';
import i18n from 'i18n';
import React from 'react';
import PropTypes from 'prop-types';
import {Button} from 'reactstrap';

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
  if (versionState === 'draft') {
    title = i18n._t('LinkField.LINK_DRAFT_TITLE', 'Link has draft changes');
    label = i18n._t('LinkField.LINK_DRAFT_LABEL', 'Draft');
  } else if (versionState === 'modified') {
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
  canDelete
}) => {
  const classes = {
    'link-picker__link': true,
    'form-control': true,
  };
  if (versionState) {
    classes[` link-picker__link--${versionState}`] = true;
  }
  const className = classnames(classes);
  const deleteText = ['unversioned', 'unsaved'].includes(versionState)
    ? i18n._t('LinkField.DELETE', 'Delete')
    : i18n._t('LinkField.ARCHIVE', 'Archive');
  return <div className={className}>
    <Button className={`link-picker__button ${typeIcon}`}  color="secondary" onClick={stopPropagation(onClick)}>
      <div className="link-picker__link-detail">
      <div className="link-picker__title">
        <span className="link-picker__title-text">{title}</span>
        {getVersionedBadge(versionState)}
      </div>
      <small className="link-picker__type">
        {typeTitle}:&nbsp;
        <span className="link-picker__url">{description}</span>
      </small>
      </div>
    </Button>
    {canDelete &&
      <Button className="link-picker__delete" color="link" onClick={stopPropagation(() => onDelete(id))}>{deleteText}</Button>
    }
  </div>
};

LinkPickerTitle.propTypes = {
  id: PropTypes.number.isRequired,
  title: PropTypes.string,
  description: PropTypes.string,
  versionState: PropTypes.string,
  typeTitle: PropTypes.string.isRequired,
  typeIcon: PropTypes.string.isRequired,
  onDelete: PropTypes.func.isRequired,
  onClick: PropTypes.func.isRequired,
  canDelete: PropTypes.bool.isRequired,
};

export default LinkPickerTitle;
