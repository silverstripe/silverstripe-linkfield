/* eslint-disable */
import PropTypes from 'prop-types';

const LinkType = PropTypes.shape({
  key: PropTypes.string.isRequired,
  title: PropTypes.string.isRequired,
  icon: PropTypes.string.isRequired,
});

export default LinkType;
