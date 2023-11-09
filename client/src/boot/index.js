/* global document */
/* eslint-disable */
import registerComponents from './registerComponents';
import registerQueries from './registerQueries';

document.addEventListener('DOMContentLoaded', () => {
  registerComponents();
  registerQueries();
});
