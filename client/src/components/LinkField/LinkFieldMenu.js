import i18n from 'i18n';
import React, {useState, setState} from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem } from 'reactstrap';
import classnames from 'classnames';
import LinkType from 'types/LinkType';

const LinkFieldMenu = ({ types, onSelect }) => {
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => setIsOpen(prevState => !prevState);


  return (
    <Dropdown
      isOpen={isOpen}
      toggle={toggle}
      className="link-field__menu"
    >
      <DropdownToggle caret>{i18n._t('Link.ADD_LINK', 'Add Link to Page')}</DropdownToggle>
      <DropdownMenu>
        {types.map(({key, title}) =>
            <DropdownItem key={key} onClick={() => onSelect(key)}>{title}</DropdownItem>
        )}
      </DropdownMenu>
  </Dropdown>
  );
};

LinkFieldMenu.propTypes = {
  types: PropTypes.arrayOf(LinkType).isRequired,
  onSelect: PropTypes.func.isRequired
};

export default LinkFieldMenu;
