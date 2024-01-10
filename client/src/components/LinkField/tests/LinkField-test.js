/* global jest, test */
import React from 'react';
import { render, screen } from '@testing-library/react';
import { Component as LinkField } from '../LinkField';

let doResolve;

jest.mock('lib/Backend', () => ({
  get: () => new Promise((resolve) => {
    doResolve = resolve;
  })
}));

window.ss.config = {
  sections: [
    {
      name: 'SilverStripe\\LinkField\\Controllers\\LinkFieldController',
      form: {
        linkForm: {
          dataUrl: 'http://example.com/mock-endpoint'
        }
      }
    }
  ]
};

function makeProps(obj = {}) {
  return {
    value: 123,
    onChange: () => {},
    types: [],
    actions: {
      toasts: {
        success: () => {},
        error: () => {}
      }
    },
    isMulti: false,
    canCreate: true,
    ownerID: 123,
    ownerClass: 'Page',
    ownerRelation: 'MyRelation',
    ...obj
  };
}

test('LinkField will render save-record-first div if ownerID is 0', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 0
  })}
  />);
  expect(container.querySelectorAll('.link-field__save-record-first')).toHaveLength(1);
  expect(container.querySelectorAll('.link-field__loading')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker')).toHaveLength(0);
});

test('LinkField will render loading indicator if ownerID is not 0', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 1

  })}
  />);
  expect(container.querySelectorAll('.link-field__save-record-first')).toHaveLength(0);
  expect(container.querySelectorAll('.link-field__loading')).toHaveLength(1);
  expect(container.querySelectorAll('.link-picker')).toHaveLength(0);
});

test('LinkField will render link-picker if ownerID is not 0 and has finished loading', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 1
  })}
  />);
  doResolve();
  await screen.findByText('Add Link');
  expect(container.querySelectorAll('.link-field__save-record-first')).toHaveLength(0);
  expect(container.querySelectorAll('.link-field__loading')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker')).toHaveLength(1);
});
