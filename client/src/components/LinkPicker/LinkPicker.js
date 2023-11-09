/* eslint-disable */
import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkPickerMenu from './LinkPickerMenu';
import LinkPickerTitle from './LinkPickerTitle';

const LinkPicker = ({ title, description, typeTitle, types, onSelect, onEdit, onClear }) => (
  <div className={classnames('link-picker', 'form-control', {'link-picker--selected': typeTitle ? true : false})}>
    {!typeTitle && <LinkPickerMenu types={types} onSelect={onSelect} /> }
    {typeTitle && <LinkPickerTitle
      title={title}
      description={description}
      typeTitle={typeTitle}
      onClear={onClear}
      onClick={() => onEdit()}
    />}
  </div>
);

LinkPicker.propTypes = {
  ...LinkPickerMenu.propTypes,
  title: PropTypes.string,
  description: PropTypes.string,
  typeTitle: PropTypes.string.isRequired,
  onEdit: PropTypes.func.isRequired,
  onClear: PropTypes.func.isRequired,
  onSelect: PropTypes.func.isRequired,
};

export {LinkPicker as Component};

export default LinkPicker;
