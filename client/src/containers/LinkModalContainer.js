/* eslint-disable */
import React from 'react';
import { loadComponent } from 'lib/Injector';
import PropTypes from 'prop-types';
import LinkType from 'types/LinkType';

/**
 * Contains the LinkModal and determines which modal component to render based on the link type.
 */
const LinkModalContainer = ({ types, typeKey, linkID = 0, isOpen, onSuccess, onClosed}) => {
  if (!typeKey) {
    return false;
  }

  const type = types.hasOwnProperty(typeKey) ? types[typeKey] : {};
  const handlerName = type && type.hasOwnProperty('handlerName')
    ? type.handlerName
    : 'FormBuilderModal';
  const LinkModal = loadComponent(`LinkModal.${handlerName}`);

  return <LinkModal
    typeTitle={type.title || ''}
    typeKey={typeKey}
    linkID={linkID}
    isOpen={isOpen}
    onSuccess={onSuccess}
    onClosed={onClosed}
  />;
}

LinkModalContainer.propTypes = {
  types: PropTypes.objectOf(LinkType).isRequired,
  typeKey: PropTypes.string.isRequired,
  linkID: PropTypes.number,
  isOpen: PropTypes.bool.isRequired,
  onSuccess: PropTypes.func.isRequired,
  onClosed: PropTypes.func.isRequired,
};

export default LinkModalContainer;
