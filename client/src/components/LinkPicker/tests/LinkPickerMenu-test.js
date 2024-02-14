/* global jest, test */

import React from 'react';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkPickerMenu from '../LinkPickerMenu';

function makeProps(obj = {}) {
  return {
    types: [
      { key: 'sitetree', title: 'Page', icon: 'font-icon-page', allowed: true },
      { key: 'external', title: 'External URL', icon: 'font-icon-link', allowed: true },
      { key: 'email', title: 'Email', icon: 'font-icon-email', allowed: true },
      { key: 'phone', title: 'Phone', icon: 'font-icon-phone', allowed: true },
    ],
    onSelect: jest.fn(),
    onKeyDownEdit: jest.fn(),
    ...obj
  };
}

test('LinkPickerMenu render() should display link list', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerMenu {...makeProps()} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.dropdown-item')).toHaveLength(4);
  expect(container.querySelectorAll('.dropdown-item')[0]).toHaveTextContent('Page');
  expect(container.querySelectorAll('.dropdown-item')[1]).toHaveTextContent('External URL');
  expect(container.querySelectorAll('.dropdown-item')[2]).toHaveTextContent('Email');
  expect(container.querySelectorAll('.dropdown-item')[3]).toHaveTextContent('Phone');
});

test('LinkPickerMenu render() should display link list with allowed SiteTreeLink and EmailLink', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerMenu {...makeProps(
      { types: [
        { key: 'sitetree', title: 'Page', icon: 'font-icon-page', allowed: true },
        { key: 'email', title: 'Email', icon: 'font-icon-email', allowed: true },
        { key: 'phone', title: 'Phone', icon: 'font-icon-phone', allowed: false },
        { key: 'external', title: 'External URL', icon: 'font-icon-link', allowed: false },
      ] })}
    />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.dropdown-item')).toHaveLength(2);
  expect(container.querySelectorAll('.dropdown-item')[0]).toHaveTextContent('Page');
  expect(container.querySelectorAll('.dropdown-item')[0].firstChild).toHaveClass('font-icon-page');
  expect(container.querySelectorAll('.dropdown-item')[1]).toHaveTextContent('Email');
  expect(container.querySelectorAll('.dropdown-item')[1].firstChild).toHaveClass('font-icon-email');
});

test('LinkPickerMenu onSelect() should call onSelect with selected type', async () => {
  const onSelect = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerMenu {...makeProps({ onSelect })} />
  </LinkFieldContext.Provider>);
  userEvent.click(container.querySelectorAll('.dropdown-item')[1]);
  await waitFor(() => {
    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect).toHaveBeenCalledWith('external');
  });
});

test('LinkPickerMenu onKeyDownEdit() should call onKeyDownEdit with selected type', async () => {
  const onKeyDownEdit = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerMenu {...makeProps({ onKeyDownEdit })} />
  </LinkFieldContext.Provider>);
  container.querySelector('.dropdown-item').focus();
  userEvent.keyboard('{enter}');
  await waitFor(() => expect(onKeyDownEdit).toHaveBeenCalledTimes(1));
});
