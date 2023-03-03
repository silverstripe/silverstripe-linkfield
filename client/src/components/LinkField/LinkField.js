import React, { Fragment, useState } from 'react';
import { compose } from 'redux';
import { inject, injectGraphql, loadComponent } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';

const LinkField = ({ id, loading, Loading, data, LinkPicker, onChange, types, linkDescription, ...props }) => {
  if (loading) {
    return <Loading />;
  }

  const [editing, setEditing] = useState(false);
  const [newTypeKey, setNewTypeKey] = useState('');

  const onClear = (event) => {
    if (typeof onChange !== 'function') {
      return;
    }

    onChange(event, { id, value: {} });
  };

  const { typeKey } = data;
  const type = types[typeKey];
  const modalType = newTypeKey ? types[newTypeKey] : type;


  const linkProps = {
    title: data ? data.Title : '',
    link: type ? { type, title: data.Title, description: linkDescription } : undefined,
    onEdit: () => { setEditing(true); },
    onClear,
    onSelect: (key) => {
      setNewTypeKey(key);
      setEditing(true);
    },
    types: Object.values(types)
  };

  const onModalSubmit = (modalData, action, submitFn) => {
    const { SecurityID, action_insert: actionInsert, ...value } = modalData;

    if (typeof onChange !== 'function') {
      return Promise.resolve();
    }

    onChange(event, { id, value });
    setEditing(false);
    setNewTypeKey('');

    return Promise.resolve();
  };

  const modalProps = {
    type: modalType,
    editing,
    onSubmit: onModalSubmit,
    onClosed: () => {
      setEditing(false);
    },
    data
  };

  const handlerName = modalType ? modalType.handlerName : 'FormBuilderModal';
  const LinkModal = loadComponent(`LinkModal.${handlerName}`);

  return <Fragment>
      <LinkPicker {...linkProps} />
      <LinkModal {...modalProps} />
    </Fragment>;
};

const stringifyData = (Component) => (({ data, value, ...props }) => {
  let dataValue = value || data;
  if (typeof dataValue === 'string') {
    dataValue = JSON.parse(dataValue);
  }
  return <Component dataStr={JSON.stringify(dataValue)} {...props} data={dataValue} />;
});

export default compose(
  inject(['LinkPicker', 'Loading']),
  injectGraphql('readLinkTypes'),
  stringifyData,
  injectGraphql('readLinkDescription'),
  fieldHolder
)(LinkField);
