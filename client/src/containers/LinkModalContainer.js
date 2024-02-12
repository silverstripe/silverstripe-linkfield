/* eslint-disable */
import React, { useState } from 'react';
import { loadComponent } from 'lib/Injector';
import PropTypes from 'prop-types';

/**
 * Contains the LinkModal and determines which modal component to render based on the link type.
 */

const LinkModalContainer = ({ types, typeKey, linkID = 0, isOpen, onSuccess, onClosed, autoFocus }) => {
  const [LinkModal, setLinkModal] = useState(null);
  if (!typeKey) {
    return false;
  }

  const type = types.hasOwnProperty(typeKey) ? types[typeKey] : {};
  const handlerName = type && type.hasOwnProperty('handlerName')
    ? type.handlerName
    : 'FormBuilderModal';

  // Use state to store the component so that it's only loaded once
  // Not doing this will cause the component to being reloaded on every render
  // which will causes bugs with validation
  if (!LinkModal) {
    setLinkModal(() => loadComponent(`LinkModal.${handlerName}`));
  }

  return <LinkModal
    typeTitle={type.title || ''}
    typeKey={typeKey}
    linkID={linkID}
    isOpen={isOpen}
    onSuccess={onSuccess}
    onClosed={onClosed}
    autoFocus={autoFocus}
  />;
}

LinkModalContainer.propTypes = {
  types: PropTypes.object.isRequired,
  typeKey: PropTypes.string.isRequired,
  linkID: PropTypes.number,
  isOpen: PropTypes.bool.isRequired,
  onSuccess: PropTypes.func.isRequired,
  onClosed: PropTypes.func.isRequired,
  autoFocus: PropTypes.bool,
};

export default LinkModalContainer;
