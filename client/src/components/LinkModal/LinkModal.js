/* eslint-disable */
import React, { useContext } from 'react'
import FormBuilderModal from 'components/FormBuilderModal/FormBuilderModal';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import url from 'url';
import qs from 'qs';
import Config from 'lib/Config';
import PropTypes from 'prop-types';

const buildSchemaUrl = (typeKey, linkID) => {
  const {schemaUrl} = Config.getSection('SilverStripe\\LinkField\\Controllers\\LinkFieldController').form.linkForm;
  const parsedURL = url.parse(schemaUrl);
  const parsedQs = qs.parse(parsedURL.query);
  parsedQs.typeKey = typeKey;
  const { ownerID, ownerClass, ownerRelation } = useContext(LinkFieldContext);
  parsedQs.ownerID = ownerID;
  parsedQs.ownerClass = ownerClass;
  parsedQs.ownerRelation = ownerRelation;
  for (const prop of ['href', 'path', 'pathname']) {
    parsedURL[prop] = `${parsedURL[prop]}/${linkID}`;
  }
  return url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
}

const LinkModal = ({ typeTitle, typeKey, linkID = 0, isOpen, onSuccess, onClosed }) => {
  if (!typeKey) {
    return false;
  }

  /**
   * Call back used by LinkModal after the form has been submitted and the response has been received
   */
  const onSubmit = async (modalData, action, submitFn) => {
    const formSchema = await submitFn();

    // slightly annoyingly, on validation error formSchema at this point will not have an errors node
    // instead it will have the original formSchema id used for the GET request to get the formSchema i.e.
    // admin/linkfield/schema/linkfield/<ItemID>
    // instead of the one used by the POST submission i.e.
    // admin/linkfield/linkForm/<LinkID>
    const hasValidationErrors = formSchema.id.match(/\/schema\/linkfield\/([0-9]+)/);
    if (!hasValidationErrors) {
      // get link id from formSchema response
      const match = formSchema.id.match(/\/linkForm\/([0-9]+)/);
      const valueFromSchemaResponse = parseInt(match[1], 10);

      onSuccess(valueFromSchemaResponse);
    }

    return Promise.resolve();
  };

  return <FormBuilderModal
    title={typeTitle}
    isOpen={isOpen}
    schemaUrl={buildSchemaUrl(typeKey, linkID)}
    identifier='Link.EditingLinkInfo'
    onSubmit={onSubmit}
    onClosed={onClosed}
  />;
}

LinkModal.propTypes = {
  typeTitle: PropTypes.string.isRequired,
  typeKey: PropTypes.string.isRequired,
  linkID: PropTypes.number,
  isOpen: PropTypes.bool.isRequired,
  onSuccess: PropTypes.func.isRequired,
  onClosed: PropTypes.func.isRequired,
};

LinkModal.defaultProps

export default LinkModal;
