import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkType from 'types/LinkType';

const LinkFieldTitle = ({ title, type, description }) => (
  <div className="link-field__link">
    <div className="link-field__title">{title}</div>
    <div className="link-field__type">
      {type.title}:
      <span className="link-field__description">{description}</span>
    </div>
  </div>
);

LinkFieldTitle.propTypes = {
  title: PropTypes.string.required,
  type: LinkType,
  description: PropTypes.string
};

export default LinkFieldTitle;
