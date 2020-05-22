/* global ss */
import jQuery from 'jquery';
import React from 'react';
import ReactDOM from 'react-dom';
import { loadComponent } from 'lib/Injector';

jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .entwine-jsonfield').entwine({

    Component: null,

    onmatch() {
      const cmsContent = this.closest('.cms-content').attr('id');
      const context = (cmsContent)
        ? { context: cmsContent }
        : {};

      const schemaComponent = this.data('schema-component');
      const ReactField = loadComponent(schemaComponent, context);

      this.setComponent(ReactField);
      this._super();
      this.refresh();
    },

    refresh() {
      const props = this.getProps();
      const ReactField = this.getComponent();
      ReactDOM.render(<ReactField {...props}  noHolder/>, this[0]);
    },

    handleChange(event, {id, value}) {
      const fieldID = $(this).data('field-id');
      $('#' + fieldID).val(JSON.stringify(value));
      this.refresh();
    },

    /**
     * Find the selected node and get attributes associated to attach the data to the form
     *
     * @returns {Object}
     */
    getProps() {
      const fieldID = $(this).data('field-id');
      const data = JSON.parse($('#' + fieldID).val());

      return {
        id: fieldID,
        data,
        onChange: this.handleChange.bind(this)
      };
    },

    /**
     * Remove the component when unmatching
     */
    onunmatch() {
      ReactDOM.unmountComponentAtNode(this[0]);
    },
  });
});
