/* eslint-disable */
import i18n from 'i18n';
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkPickerMenu from './LinkPickerMenu';
import LinkModalContainer from 'containers/LinkModalContainer';

/**
 * Component which allows users to choose a type of link to create, and opens a modal form for it.
 */
const LinkPicker = ({ types, onModalSuccess, onModalClosed, canCreate }) => {
  const [typeKey, setTypeKey] = useState('');

  /**
   * When a link type is selected, set the type key so we can open the modal.
   */
  const handleSelect = (key) => {
    setTypeKey(key);
  }

  /**
   * Callback for when the modal is closed by the user
   */
  const handleClosed = () => {
    if (typeof onModalClosed === 'function') {
      onModalClosed();
    }
    setTypeKey('');
  }

  /**
   * Callback for when the modal successfully saves a link
   */
  const handleSuccess = (value) => {
    setTypeKey('');
    onModalSuccess(value);
  }

  const shouldOpenModal = typeKey !== '';
  const className = classnames('link-picker', 'form-control');
  const typeArray = Object.values(types);
  const message = i18n._t('LinkField.CANNOT_CREATE_LINK', 'Cannot create link');

  if (!canCreate || typeArray.length === 0) {
    return (
      <div className={className}>
        <div className="link-picker__cannot-create">
          {message}
        </div>
      </div>
    );
  }

  return (
    <div className={className}>
      <LinkPickerMenu types={typeArray} onSelect={handleSelect} />
      { shouldOpenModal && <LinkModalContainer
          types={types}
          typeKey={typeKey}
          isOpen={shouldOpenModal}
          onSuccess={handleSuccess}
          onClosed={handleClosed}
        />
      }
    </div>
  );
};

LinkPicker.propTypes = {
  types: PropTypes.object.isRequired,
  onModalSuccess: PropTypes.func.isRequired,
  onModalClosed: PropTypes.func,
  canCreate: PropTypes.bool.isRequired
};

export {LinkPicker as Component};

export default LinkPicker;
