import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem, Button } from 'reactstrap';
import classnames from 'classnames';
import LinkPickerMenu from './LinkPickerMenu';
import LinkPickerTitle from './LinkPickerTitle';
import LinkType from 'types/LinkType';

const LinkPicker = ({ types, onSelect, link, onEdit, onClear }) => (
  <div
    className={classnames('link-picker', 'form-control', {'link-picker--selected': link})}>
    {link === undefined && <LinkPickerMenu types={types} onSelect={onSelect} /> }
    {link && <LinkPickerTitle {...link} onClear={onClear} onClick={() => link && onEdit && onEdit(link)}/>}
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
