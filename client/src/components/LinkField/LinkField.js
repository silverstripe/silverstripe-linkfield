import React, { Fragment, useState, useEffect } from 'react';
import { compose } from 'redux';
import { inject, loadComponent } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';

const LinkField = ({ id, onChange, dataStr, Loading, LinkPicker }) => {
  // params id, onChange, dataStr come from entwine/JsonField.js
  // params LinkPicker, Loading come from inject(['LinkPicker', 'Loading'])
  const [types, setTypes] = useState([]);
  const [description, setDescription] = useState(null);
  const [editing, setEditing] = useState(false);
  const [newTypeKey, setNewTypeKey] = useState('');

  const onClear = (event) => {
    if (typeof onChange !== 'function') {
      return;
    }

    onChange(event, { id, value: {} });
  };

  // Utility function to make a GET request to the server
  const fetchData = (path, onFetched) => {
    (async () => fetch(path))()
      .then(response => response.json())
      .then(onFetched);
  };

  // Request types data from server on initial load of component
  useEffect(() => {
    fetchData('/admin/linkfield/types', (responseJson) => setTypes(responseJson));
  }, []);

  // Request description of the link from the server when `editing` variable changes to false
  // and on initial load of component
  useEffect(() => {
    if (!editing) {
      const path = `/admin/linkfield/description?data=${encodeURI(dataStr)}`;
      fetchData(path, (responseJson) => setDescription(responseJson.description));
    }
  }, [editing]);

  if (types.length === 0 || description === null) {
    return <Loading />;
  }

  const data = JSON.parse(dataStr);
  const { typeKey } = data;
  const type = types[typeKey];
  const modalType = newTypeKey ? types[newTypeKey] : type;

  let title = data ? data.Title : '';

  if (!title) {
    title = data ? data.TitleRelField : '';
  }

  const linkProps = {
    title,
    link: type ? { type, title, description } : undefined,
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

    if (typeof onChange === 'function') {
      onChange(event, { id, value });
    }

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
  stringifyData,
  fieldHolder
)(LinkField);
