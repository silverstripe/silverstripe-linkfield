/* eslint-disable */
import React, { useContext } from 'react'
import FormBuilderModal from 'components/FormBuilderModal/FormBuilderModal';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import url from 'url';
import qs from 'qs';
import Config from 'lib/Config';
import { joinUrlPaths } from 'lib/urls';
import PropTypes from 'prop-types';

const buildSchemaUrl = (typeKey, linkID) => {
  const {schemaUrl} = Config.getSection('SilverStripe\\LinkField\\Controllers\\LinkFieldController').form.linkForm;
  const parsedURL = url.parse(schemaUrl);
  const parsedQs = qs.parse(parsedURL.query);
  parsedQs.typeKey = typeKey;
  const { ownerID, ownerClass, ownerRelation, excludeLinkTextField, inHistoryViewer } = useContext(LinkFieldContext);
  parsedQs.ownerID = ownerID;
  parsedQs.ownerClass = ownerClass;
  parsedQs.ownerRelation = ownerRelation;
  if (excludeLinkTextField) {
    parsedQs.excludeLinkTextField = true;
  }
  if (inHistoryViewer) {
    parsedQs.inHistoryViewer = '1';
  }
  for (const prop of ['href', 'path', 'pathname']) {
    parsedURL[prop] = joinUrlPaths(parsedURL[prop], linkID.toString());
  }
  return url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
}

const LinkModal = ({ typeTitle, typeKey, linkID = 0, isOpen, onSuccess, onClosed, autoFocus }) => {
  const { actions } = useContext(LinkFieldContext);

  if (!typeKey) {
    return false;
  }

  /**
   * Call back used by LinkModal after the form has been submitted and the response has been received
   */
  const onSubmit = async (modalData, action, submitFn) => {
    let formSchema = null;

    // Workaround to prevent the FormBuilder form from submitting when any nested FormBuilder forms are present
    // This will happen when a FileLink is being used and the "Choose existing" link is clicked which
    // will open up an asset-admin modal to select an existing file.
    // Clicking any of the following submit buttons will cause LinkModal to inadvertanely submit:
    // - "Insert file" which is present when the breakpoint is less than 'lg' (992px)
    // - Save/Publish button after clicking on the 'Details' tab of a selected file
    if (document.getElementById('Form_fileSelectForm_action_insert')
        || document.getElementById('Form_fileEditForm_Actions_Holder')) {
      return Promise.resolve();
    }

    try {
      formSchema = await submitFn();
    } catch (error) {
      actions.toasts.error(i18n._t('LinkField.FAILED_TO_SAVE_LINK', 'Failed to save link'))
      // Intentionally using Promise.resolve() instead of Promise.reject() as existing code in FormBuilder.js
      // will raise console warnings if we use Promise.reject(). From a UX point of view it makes no difference.
      return Promise.resolve();
    }

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
    autoFocus={autoFocus}
  />;
}

LinkModal.propTypes = {
  typeTitle: PropTypes.string.isRequired,
  typeKey: PropTypes.string.isRequired,
  linkID: PropTypes.number,
  isOpen: PropTypes.bool.isRequired,
  onSuccess: PropTypes.func.isRequired,
  onClosed: PropTypes.func.isRequired,
  autoFocus: PropTypes.bool,
};

LinkModal.defaultProps

export default LinkModal;
