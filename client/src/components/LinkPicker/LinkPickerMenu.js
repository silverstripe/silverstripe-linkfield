/* eslint-disable */
import i18n from 'i18n';
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem } from 'reactstrap';
import LinkType from 'types/LinkType';

const LinkPickerMenu = ({ types, onSelect }) => {
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => setIsOpen(prevState => !prevState);

  return (
    <Dropdown
      isOpen={isOpen}
      toggle={toggle}
      className="link-picker__menu"
    >
      <DropdownToggle className="link-picker__menu-toggle font-icon-link" caret>{i18n._t('Link.ADD_LINK', 'Add Link')}</DropdownToggle>
      <DropdownMenu>
        {types.map(({key, title}) =>
            <DropdownItem key={key} onClick={() => onSelect(key)}>{title}</DropdownItem>
        )}
      </DropdownMenu>
  </Dropdown>
  );
};

LinkPickerMenu.propTypes = {
  types: PropTypes.arrayOf(LinkType).isRequired,
  onSelect: PropTypes.func.isRequired
};

export default LinkPickerMenu;
