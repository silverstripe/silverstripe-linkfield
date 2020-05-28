import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkType from 'types/LinkType';
import {Button} from 'reactstrap';

const stopPropagation = (fn) => (e) => {
  console.log('trying to stop propagation');
  e.nativeEvent.stopImmediatePropagation();
  e.preventDefault();
  e.nativeEvent.preventDefault();
  e.stopPropagation();
  fn && fn();
}

const LinkPickerTitle = ({ title, type, description, onClear, onClick }) => (
  <Button className="link-picker__link font-icon-link" color="secondary" onClick={stopPropagation(onClick)}>
    <div className="link-picker__link-detail">
      <div className="link-picker__title">{title}</div>
      <small className="link-picker__type">
        {type.title}:&nbsp;
        <span className="link-picker__url">{description}</span>
      </small>
    </div>
    <Button className="link-picker__clear" color="link" onClick={stopPropagation(onClear)}>{i18n._t('Link.CLEAR', 'Clear')}</Button>
  </Button>
);

LinkPickerTitle.propTypes = {
  title: PropTypes.string.isRequired,
  type: LinkType,
  description: PropTypes.string,
  onClear: PropTypes.func,
  onClick: PropTypes.func
};

export default LinkPickerTitle;
