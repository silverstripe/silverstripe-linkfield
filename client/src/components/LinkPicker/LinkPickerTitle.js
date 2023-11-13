/* eslint-disable */
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

const LinkPickerTitle = ({ title, description, typeTitle, onClear, onClick }) => (
  <div className="link-picker__link" >
    <Button className="link-picker__button font-icon-link"  color="secondary" onClick={stopPropagation(onClick)}>
      <div className="link-picker__link-detail">
      <div className="link-picker__title">{title}</div>
      <small className="link-picker__type">
        {typeTitle}:&nbsp;
        <span className="link-picker__url">{description}</span>
      </small>
      </div>
    </Button>
    <Button className="link-picker__clear" color="link" onClick={stopPropagation(onClear)}>{i18n._t('LinkField.CLEAR', 'Clear')}</Button>
  </div>
);

LinkPickerTitle.propTypes = {
  title: PropTypes.string,
  description: PropTypes.string,
  typeTitle: PropTypes.string.isRequired,
  onClear: PropTypes.func.isRequired,
  onClick: PropTypes.func.isRequired,
};

export default LinkPickerTitle;
