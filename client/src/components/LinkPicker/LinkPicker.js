/* eslint-disable */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { loadComponent } from 'lib/Injector';
import LinkPickerMenu from './LinkPickerMenu';
import LinkType from 'types/LinkType';

const LinkPicker = ({ types, onSelect, onModalSuccess, onModalClosed }) => {
  const [typeKey, setTypeKey] = useState('');

  const doSelect = (key) => {
    if (typeof onSelect === 'function') {
      onSelect(key);
    }
    setTypeKey(key);
  }

  const onClosed = () => {
    if (typeof onModalClosed === 'function') {
      onModalClosed();
    }
    setTypeKey('');
  }

  const onSuccess = (value) => {
    setTypeKey('');
    onModalSuccess(value);
  }

  const type = types.hasOwnProperty(typeKey) ? types[typeKey] : {};
  const modalType = typeKey ? types[typeKey] : type;
  const handlerName = modalType && modalType.hasOwnProperty('handlerName')
    ? modalType.handlerName
    : 'FormBuilderModal';
  const LinkModal = loadComponent(`LinkModal.${handlerName}`);

  const isOpen = Boolean(typeKey);

  const modalProps = {
    typeTitle: type.title || '',
    typeKey,
    isOpen,
    onSuccess: onSuccess,
    onClosed: onClosed,
  };

  return (
    <div className={classnames('link-picker', 'form-control')}>
      <LinkPickerMenu types={Object.values(types)} onSelect={doSelect} />
      { isOpen && <LinkModal {...modalProps} /> }
    </div>
  );
};

LinkPicker.propTypes = {
  types: PropTypes.objectOf(LinkType).isRequired,
  onSelect: PropTypes.func,
  onModalSuccess: PropTypes.func.isRequired,
  onModalClosed: PropTypes.func,
};

export {LinkPicker as Component};

export default LinkPicker;
