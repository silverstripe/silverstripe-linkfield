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
      this.getInputField().val(props.value);
      const ReactField = this.getComponent();
      const Root = this.getRoot();
      Root.render(<ReactField {...props} noHolder/>);
    },

    handleChange(value) {
      this.getInputField().data('value', value);
      this.refresh();
    },

    /**
     * Find the selected node and get attributes associated to attach the data to the form
     *
     * @returns {Object}
     */
    getProps() {
      const inputField = this.getInputField();
      return {
        value: inputField.data('value'),
        ownerID: inputField.data('owner-id'),
        ownerClass: inputField.data('owner-class'),
        ownerRelation: inputField.data('owner-relation'),
        onChange: this.handleChange.bind(this),
        isMulti: this.data('is-multi') ?? false,
        types: this.data('types') ?? {},
        canCreate: inputField.data('can-create') ? true : false,
      };
    },

    /**
     * Get the <input> field that represents the linkfield.
     */
    getInputField() {
      const fieldID = this.data('field-id');
      return $(`#${fieldID}`);
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
