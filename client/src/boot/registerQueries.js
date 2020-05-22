import Injector from 'lib/Injector';
import readLinkTypes from 'state/linkTypes/readLinkTypes';

const registerQueries = () => {
  Injector.query.register('readLinkTypes', readLinkTypes);
};
export default registerQueries;
