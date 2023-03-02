/* eslint-disable */
import Injector from 'lib/Injector';
import readLinkTypes from 'state/linkTypes/readLinkTypes';
import readLinkDescription from 'state/linkDescription/readLinkDescription';

const registerQueries = () => {
  Injector.query.register('readLinkTypes', readLinkTypes);
  Injector.query.register('readLinkDescription', readLinkDescription);
};
export default registerQueries;
