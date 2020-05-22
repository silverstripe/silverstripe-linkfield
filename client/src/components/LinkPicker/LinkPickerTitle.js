import i18n from 'i18n';
import React from 'react';
import { inject } from 'lib/Injector';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import LinkType from 'types/LinkType';

const LinkPickerTitle = ({ title, type, description }) => (
  <div className="link-picker__link">
    <div className="link-picker__title">{title}</div>
    <div className="link-picker__type">
      {type.title}:
      <span className="link-picker__description">{description}</span>
    </div>
  </div>
);

LinkPickerTitle.propTypes = {
  title: PropTypes.string.isRequired,
  type: LinkType,
  description: PropTypes.string
};

export default LinkPickerTitle;
