import i18n from 'i18n';
import React, { Component, Fragment, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { withApollo } from 'react-apollo';
import { inject, injectGraphql } from 'lib/Injector';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import PropTypes from 'prop-types';
import FormBuilderModal from 'components/FormBuilderModal/FormBuilderModal';
import url from 'url';
import qs from 'qs';
import Config from 'lib/Config';

const leftAndMain = 'SilverStripe\\Admin\\LeftAndMain';

const buildSchemaUrl = (key, data) => {

  const {schemaUrl} = Config.getSection(leftAndMain).form.DynamicLink;

  const parsedURL = url.parse(schemaUrl);
  const parsedQs = qs.parse(parsedURL.query);
  parsedQs.key = key;
  if (data) {
    parsedQs.data = JSON.stringify(data);
  }
  return url.format({ ...parsedURL, search: qs.stringify(parsedQs)});
}

const Modal = ({type, editing, data, ...props}) => {
  if (!type) {
    return false;
  }

  return <FormBuilderModal
    title={type.title}
    isOpen={editing}
    schemaUrl={buildSchemaUrl(type.key, data)}
    identifier='Link.EditingLinkInfo'
    {...props}
  />;
}

const LinkField = ({id, loading, Loading, data, LinkPicker, onChange, types, ...props}) => {
  if (loading) {
    return <Loading />
  }

  const [editing, setEditing] = useState(false);
  const [newTypeKey, setNewTypeKey] = useState('');

  const onClear = (event) => {
    typeof onChange === 'function' && onChange(event, { id, value: {}})
  }

  const { typeKey } = data;
  const type = types[typeKey];
  const modalType = newTypeKey ? types[newTypeKey] : type;


  const linkProps = {
    title: data ? data.Title : '',
    link: type ? {type, title: data.Title, ...data} : undefined,
    onEdit: () => {setEditing(true)},
    onClear,
    onSelect: (key) => {
      setNewTypeKey(key);
      setEditing(true);
    },
    types: Object.values(types)
  }

  const onModalSubmit = (data, action, submitFn) => {
    const {SecurityID, action_insert, ...value} = data;
    typeof onChange === 'function' && onChange(event, { id, value})
    setEditing(false);
    setNewTypeKey('');
    return Promise.resolve();
  }

  const modalProps = {
    type: modalType,
    editing,
    onSubmit: onModalSubmit,
    onClosed: () => {
      setEditing(false);
    },
    data
  };

  return <Fragment>
      <LinkPicker {...linkProps} />
      <Modal {...modalProps} />
    </Fragment>;
}


export default compose(
  inject(['LinkPicker', 'Loading']),
  injectGraphql('readLinkTypes'),
  withApollo,
  fieldHolder
)(LinkField);
