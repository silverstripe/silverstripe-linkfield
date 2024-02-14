/* global jest, test, expect, document */
import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
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
    types: {
      mylink: {
        key: 'mylink',
        title: 'My Link',
        handlerName: 'FormBuilderModal',
        priority: 100,
        icon: 'font-icon-link',
        allowed: true
      }
    },
    actions: {
      toasts: {
        success: () => {},
        error: () => {}
      }
    },
    isMulti: false,
    canCreate: true,
    readonly: false,
    disabled: false,
    inHistoryViewer: false,
    ownerID: 123,
    ownerClass: 'Page',
    ownerRelation: 'MyRelation',
    ...obj
  };
}

test('LinkField returns list of links if they exist', async () => {
  const { container } = render(<LinkField {...makeProps({
    isMulti: true,
    value: [1, 2],
    types: {
      sitetree: { key: 'sitetree', title: 'Page', icon: 'font-icon-page', allowed: true },
      email: { key: 'email', title: 'Email', icon: 'font-icon-email', allowed: true },
    },
  })}
  />);

  await doResolve({ json: () => ({
    1: {
      Title: 'Page title',
      typeKey: 'sitetree',
    },
    2: {
      Title: 'Email title',
      typeKey: 'email',
    },
  }) });
  await screen.findByText('Page title');
  expect(container.querySelectorAll('.link-picker__button')).toHaveLength(2);
  expect(container.querySelectorAll('.link-picker__button.font-icon-page')[0]).toHaveTextContent('Page title');
  expect(container.querySelectorAll('.link-picker__button.font-icon-email')[0]).toHaveTextContent('Email title');
});

test('LinkField will render disabled state if disabled is true', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 1,
    disabled: true,
  })}
  />);
  doResolve();
  await screen.findByText('Cannot create link');
  expect(container.querySelectorAll('.link-picker')).toHaveLength(1);
  expect(container.querySelectorAll('.link-picker')[0]).toHaveTextContent('Cannot create link');
});

test('LinkField will render readonly state if readonly is true', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 1,
    readonly: true,
    value: null,
  })}
  />);
  await screen.findByText('Cannot create link');
  expect(container.querySelectorAll('.link-picker')).toHaveLength(1);
  expect(container.querySelectorAll('.link-picker')[0]).toHaveTextContent('Cannot create link');
});

test('LinkField tab order', async () => {
  const user = userEvent.setup();
  const { container } = render(<LinkField {...makeProps({
    isMulti: true,
    value: [123, 456],
  })}
  />);

  await doResolve({ json: () => ({
    123: {
      Title: 'First title',
      Sort: 1,
      typeKey: 'mylink',
    },
    456: {
      Title: 'Second title',
      Sort: 2,
      typeKey: 'mylink',
    },
  }) });
  await screen.findByText('First title');

  expect(Array.from(container.querySelectorAll('.link-picker__title-text')).map(el => el.innerHTML))
    .toStrictEqual(['First title', 'Second title']);

  const linkPicker123 = container.querySelector('#link-picker__link-123');
  const button123 = linkPicker123.querySelector('.link-picker__button');
  const dragHandle123 = linkPicker123.querySelector('.link-picker__drag-handle');
  const linkPicker456 = container.querySelector('#link-picker__link-456');
  const button456 = linkPicker456.querySelector('.link-picker__button');
  const dragHandle456 = linkPicker456.querySelector('.link-picker__drag-handle');

  // Focus starts on document <body>
  expect(container.parentNode).toHaveFocus();
  await user.tab();
  expect(container.querySelector('.link-picker__menu-toggle')).toHaveFocus();
  // note need to tab twice because jest will focus on the .dropdown-item, however in a real browser
  // this doesn't happen because it will have a display of none at this point
  await user.tab();
  await user.tab();
  expect(dragHandle123).toHaveFocus();
  await user.tab();
  expect(button123).toHaveFocus();
  await user.tab();
  expect(dragHandle456).toHaveFocus();
  await user.tab();
  expect(button456).toHaveFocus();

  // Note that we cannot test keyboard sorting with up + down keys in jest because jsdom does not have a layout engine
  // e.g. el.getBoundingClientRect() will always return 0,0,0,0
});

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
  expect(container.querySelectorAll('.link-picker')).toHaveLength(1);
});

test('LinkField will render link-picker if ownerID is not 0 and isMulti and has finished loading', async () => {
  const { container } = render(<LinkField {...makeProps({
    ownerID: 1,
    isMulti: true,
  })}
  />);
  await doResolve({ json: () => ({
    123: {
      Title: 'First title',
      Sort: 1,
      typeKey: 'mylink',
    },
  }) });
  await screen.findByText('First title');

  expect(container.querySelectorAll('.link-field__save-record-first')).toHaveLength(0);
  expect(container.querySelectorAll('.link-field__loading')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker')).toHaveLength(1);
});
