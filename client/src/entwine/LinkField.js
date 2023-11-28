/* global ss */
/* eslint-disable */
import jQuery from 'jquery';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { loadComponent } from 'lib/Injector';

jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .entwine-linkfield').entwine({

    Component: null,
    Root: null,

    onmatch() {
      const cmsContent = this.closest('.cms-content').attr('id');
      const context = (cmsContent)
        ? { context: cmsContent }
        : {};

      const schemaComponent = this.data('schema-component');
      const ReactField = loadComponent(schemaComponent, context);

      this.setComponent(ReactField);
      this.setRoot(ReactDOM.createRoot(this[0]))
      this._super();
      this.refresh();
    },

    refresh() {
      const props = this.getProps();
      const ReactField = this.getComponent();
      const Root = this.getRoot();
      Root.render(<ReactField {...props} noHolder/>);
    },

    handleChange(value) {
      const fieldID = $(this).data('field-id');
      $('#' + fieldID).val(value);
      this.refresh();
    },

    /**
     * Find the selected node and get attributes associated to attach the data to the form
     *
     * @returns {Object}
     */
    getProps() {
      const fieldID = $(this).data('field-id');
      const value = Number($('#' + fieldID).val());
      return {
        value,
        onChange: this.handleChange.bind(this)
      };
    },

    /**
     * Remove the component when unmatching
     */
    onunmatch() {
      const Root = this.getRoot();
      if (Root) {
        Root.unmount();
      }
    },
  });
});
