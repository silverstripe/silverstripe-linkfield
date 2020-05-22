import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem, Button } from 'reactstrap';
import classnames from 'classnames';
import LinkPickerMenu from './LinkPickerMenu';
import LinkPickerTitle from './LinkPickerTitle';
import LinkType from 'types/LinkType';

const stopPropagation = (fn) => (e) => {
  e.preventDefault();
  e.stopPropagation();
  fn && fn();
}

const LinkPicker = ({ types, onSelect, link, onEdit, onClear }) => (
  <div
    className={classnames('link-picker', 'font-icon-link', {'link-picker--selected': link})}
    onClick={() => link && onEdit && onEdit(link)}
    role='button'
    >
    {link === undefined && <LinkPickerMenu types={types} onSelect={onSelect} /> }
    {link && <LinkPickerTitle {...link}/>}
    {link && <Button className="link-picker__clear" color="link" onClick={stopPropagation(onClear)}>{i18n._t('Link.CLEAR', 'Clear')}</Button>}
  </div>
);

LinkPicker.propTypes = {
  ...LinkPickerMenu.propTypes,
  link: PropTypes.shape(LinkPickerTitle.propTypes),
  onEdit: PropTypes.func,
  onClear: PropTypes.func,
};


export {LinkPicker as Component};

export default LinkPicker;
