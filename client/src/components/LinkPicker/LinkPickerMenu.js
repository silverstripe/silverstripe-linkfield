/* eslint-disable */
import i18n from 'i18n';
import React, { useContext, useState } from 'react';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem } from 'reactstrap';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkType from 'types/LinkType';

const LinkPickerMenu = ({ types, onSelect, onKeyDownEdit }) => {
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => setIsOpen(prevState => !prevState);
  const { loading } = useContext(LinkFieldContext);

  const handleKeyDown = (event) => {
    if (['Enter', 'Space'].includes(event.code)) {
      onKeyDownEdit();
    }
  }

  const ariaLabel = i18n._t('LinkField.ADD_LINK', 'Add link');

  return <Dropdown
    disabled={loading}
    isOpen={isOpen}
    toggle={toggle}
    className="link-picker__menu"
  >
      <DropdownToggle
        className="link-picker__menu-toggle font-icon-plus-1"
        caret
        color="secondary"
        aria-label={ariaLabel}
      >
        {i18n._t('LinkField.ADD_LINK', 'Add Link')}
      </DropdownToggle>
      <DropdownMenu>
        {types.map(({key, title, icon, allowed}) => {
          return allowed &&
            <DropdownItem
              key={key}
              onClick={() => {onSelect(key)}}
              onKeyDown={handleKeyDown}
            >
              <span className={`link-picker__menu-icon ${icon}`}></span>
              {title}
            </DropdownItem>
          }
        )}
      </DropdownMenu>
  </Dropdown>
};

LinkPickerMenu.propTypes = {
  types: PropTypes.arrayOf(LinkType).isRequired,
  onSelect: PropTypes.func.isRequired,
  onKeyDownEdit: PropTypes.func.isRequired,
};

export default LinkPickerMenu;
