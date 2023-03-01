/* global document */
/* eslint-disable */
import Config from 'lib/Config';
import registerReducers from './registerReducers';
import registerComponents from './registerComponents';
import registerQueries from './registerQueries';

document.addEventListener('DOMContentLoaded', () => {
  registerComponents();

  registerQueries();

  registerReducers();
});
