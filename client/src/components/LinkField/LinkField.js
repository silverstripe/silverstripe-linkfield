import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import { Dropdown, DropdownToggle, DropdownMenu, DropdownItem, Button } from 'reactstrap';
import classnames from 'classnames';
import LinkFieldMenu from './LinkFieldMenu';
import LinkFieldTitle from './LinkFieldTitle';
import LinkType from 'types/LinkType';

const stopPropagation = (fn) => (e) => {
  e.preventDefault();
  e.stopPropagation();
  fn && fn();
}

const LinkField = ({ types, onSelect, link, onEdit, onClear }) => (
  <div
    className={classnames('link-field', 'font-icon-link', {'link-field--selected': link})}
    onClick={() => link && onEdit && onEdit(link)}
    aria-role='button'
    >
    {link === undefined && <LinkFieldMenu types={types} onSelect={onSelect} /> }
    {link && <LinkFieldTitle {...link}/>}
    {link && <Button className="link-field__clear" color="link" onClick={stopPropagation(onClear)}>{i18n._t('Link.CLEAR', 'Clear')}</Button>}
  </div>
);

LinkField.propTypes = {
  ...LinkFieldMenu.propTypes,
  link: PropTypes.shape(LinkFieldTitle.propTypes),
  onEdit: PropTypes.func,
  onClear: PropTypes.func,
};


export {LinkField as Component};

export default LinkField;
