/* eslint-disable */
import React from 'react';
import FormBuilderModal from 'components/FormBuilderModal/FormBuilderModal';
import url from 'url';
import qs from 'qs';
import Config from 'lib/Config';
import PropTypes from 'prop-types';

const buildSchemaUrl = (typeKey, linkID) => {
  const {schemaUrl} = Config.getSection('SilverStripe\\LinkField\\Controllers\\LinkFieldController').form.linkForm;
  const parsedURL = url.parse(schemaUrl);
  const parsedQs = qs.parse(parsedURL.query);
  parsedQs.typeKey = typeKey;
  for (const prop of ['href', 'path', 'pathname']) {
    parsedURL[prop] = `${parsedURL[prop]}/${linkID}`;
  }
  return url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
}

const LinkModal = ({ typeTitle, typeKey, linkID, editing, onSubmit, onClosed}) => {
  if (!typeKey) {
    return false;
  }
  return <FormBuilderModal
    title={typeTitle}
    isOpen={editing}
    schemaUrl={buildSchemaUrl(typeKey, linkID)}
    identifier='Link.EditingLinkInfo'
    onSubmit={onSubmit}
    onClosed={onClosed}
  />;
}

LinkModal.propTypes = {
  typeTitle: PropTypes.string.isRequired,
  typeKey: PropTypes.string.isRequired,
  linkID: PropTypes.number.isRequired,
  editing: PropTypes.bool.isRequired,
  onSubmit: PropTypes.func.isRequired,
  onClosed: PropTypes.func.isRequired,
};

export default LinkModal;
