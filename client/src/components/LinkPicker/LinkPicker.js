import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkPickerMenu from './LinkPickerMenu';
import LinkPickerTitle from './LinkPickerTitle';

const LinkPicker = ({ types, onSelect, link, onEdit, onClear }) => (
  <div className={classnames('link-picker', 'form-control', { 'link-picker--selected': link })}>
    {link === undefined && <LinkPickerMenu types={types} onSelect={onSelect} /> }
    {
      link && <LinkPickerTitle
        {...link}
        onClear={onClear}
        onClick={() => link && onEdit && onEdit(link)}
      />
    }
  </div>
);

LinkPicker.propTypes = {
  ...LinkPickerMenu.propTypes,
  link: PropTypes.shape(LinkPickerTitle.propTypes),
  onEdit: PropTypes.func,
  onClear: PropTypes.func,
};


export { LinkPicker as Component };

export default LinkPicker;
