/* eslint-disable */
import i18n from 'i18n';
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem } from 'reactstrap';
import LinkType from 'types/LinkType';

const LinkPickerMenu = ({ types, onSelect }) => {
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => setIsOpen(prevState => !prevState);
  return <Dropdown
    isOpen={isOpen}
    toggle={toggle}
    className="link-picker__menu"
  >
      <DropdownToggle className="link-picker__menu-toggle font-icon-plus-1" caret>
        {i18n._t('LinkField.ADD_LINK', 'Add Link')}
      </DropdownToggle>
      <DropdownMenu>
        {types.map(({key, title, icon}) =>
            <DropdownItem key={key} onClick={() => onSelect(key)}>
              <span className={`link-picker__menu-icon ${icon}`}></span>
              {title}
            </DropdownItem>
        )}
      </DropdownMenu>
  </Dropdown>
};

LinkPickerMenu.propTypes = {
  types: PropTypes.arrayOf(LinkType).isRequired,
  onSelect: PropTypes.func.isRequired,
};

export default LinkPickerMenu;
