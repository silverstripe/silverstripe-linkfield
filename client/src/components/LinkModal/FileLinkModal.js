/* global tinymce, editorIdentifier, ss */
/* eslint-disable */
import i18n from 'i18n';
import React, {useEffect} from 'react';
import InsertMediaModal from 'containers/InsertMediaModal/InsertMediaModal';
import {connect} from "react-redux";

const FileLinkModal = ({type, editing, data, actions, onSubmit, ...props}) => {

  if (!type) {
    return false;
  }

  useEffect(() => {
    if (editing) {
      actions.initModal();
    } else {
      actions.reset();
    }
  }, [editing])

  const attrs = data ? {
    ID: data.FileID,
    Description: data.Title,
    TargetBlank: data.OpenInNew ? true : false,
  } : {};

  const onInsert = ({ID, Description, TargetBlank}) => {
    return onSubmit({
      FileID: ID,
      Title: Description,
      OpenInNew: TargetBlank,
      typeKey: type.key
    }, '', () => {});
  };

  return <InsertMediaModal
    isOpen={editing}
    type="insert-link"
    title={false}
    bodyClassName="modal__dialog"
    className="insert-link__dialog-wrapper--internal"
    fileAttributes={attrs}
    onInsert={onInsert}
    {...props}
    />;
}

function mapStateToProps() {
  return {};
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      initModal: () => dispatch({
        type: 'INIT_FORM_SCHEMA_STACK',
        payload: { formSchema: { type: 'insert-link', nextType: 'admin' } },
      }),
      reset: () => dispatch({ type: 'RESET' }),
    },
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(FileLinkModal);

