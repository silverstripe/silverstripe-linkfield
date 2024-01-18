/* global jest, test */
import React from 'react';
import { render, fireEvent, act } from '@testing-library/react';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkPicker from '../LinkPicker';

function makeProps(obj = {}) {
  return {
    types: { phone: { key: 'phone', title: 'Phone', icon: 'font-icon-phone' } },
    canCreate: true,
    onModalSuccess: () => {},
    onModalClosed: () => {},
    ...obj
  };
}

test('LinkPickerMenu render() should display toggle if can create', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPicker {...makeProps({ canCreate: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__menu-toggle')).toHaveLength(1);
  expect(container.querySelectorAll('.link-picker__cannot-create')).toHaveLength(0);
});

test('LinkPickerMenu render() should display cannot create message if cannot create', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPicker {...makeProps({ canCreate: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__menu-toggle')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker__cannot-create')).toHaveLength(1);
});

test('LinkPickerMenu render() should display cannot create message if types is empty', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPicker {...makeProps({ types: {} })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__menu-toggle')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker__cannot-create')).toHaveLength(1);
});

test('LinkPickerMenu render() should display link type icon if can create', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPicker {...makeProps({ canCreate: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__menu-icon.font-icon-phone')).toHaveLength(1);
});

test('LinkPickerMenu should open dropdown on click when not loading', async () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPicker {...makeProps()} />
  </LinkFieldContext.Provider>);
  await act(async () => {
    await fireEvent.click(container.querySelector('button.link-picker__menu-toggle'));
  });
  expect(container.querySelectorAll('.dropdown-menu.show')).toHaveLength(1);
});

test('LinkPickerMenu should not open dropdown on click while loading', async () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: true }}>
    <LinkPicker {...makeProps()} />
  </LinkFieldContext.Provider>);
  await act(async () => {
    await fireEvent.click(container.querySelector('button.link-picker__menu-toggle'));
  });
  expect(container.querySelectorAll('.dropdown-menu.show')).toHaveLength(0);
});
