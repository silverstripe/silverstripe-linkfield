/* eslint-disable */
import i18n from 'i18n';
import React, { useContext, useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem } from 'reactstrap';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkType from 'types/LinkType';

const LinkPickerMenu = ({ types, onSelect, dropdownToggleRef }) => {
  const allowedTypes = useMemo(() => types.filter(type => type.allowed), [types]);
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => {
    if (allowedTypes.length === 1) {
      return onSelect(allowedTypes[0].key);
    }
    setIsOpen(prevState => !prevState);
  };
  const { loading } = useContext(LinkFieldContext);
  const ariaLabel = i18n._t('LinkField.ADD_NEW_LINK', 'Add new link');

  return <Dropdown
    disabled={loading}
    isOpen={isOpen}
    toggle={toggle}
    className="link-picker__menu"
  >
    <DropdownToggle
      className="link-picker__menu-toggle"
      caret={allowedTypes.length > 1}
      color="secondary"
      aria-label={ariaLabel}
      innerRef={dropdownToggleRef}
    >
      <span className="font-icon-plus-1" aria-hidden="true" />
      {i18n._t('LinkField.ADD_NEW_LINK', 'Add new Link')}
    </DropdownToggle>
    <DropdownMenu>
      {allowedTypes.map(({ key, title, icon }) => (
        <DropdownItem
          key={key}
          onClick={() => { onSelect(key) }}
        >
          <span className={`link-picker__menu-icon ${icon}`} aria-hidden="true" />
          <span className={`link-picker__menu-title`}>{title}</span>
        </DropdownItem>
      ))}
    </DropdownMenu>
  </Dropdown>
};

LinkPickerMenu.propTypes = {
  types: PropTypes.arrayOf(LinkType).isRequired,
  onSelect: PropTypes.func.isRequired,
  dropdownToggleRef: PropTypes.object.isRequired,
};

export default LinkPickerMenu;
