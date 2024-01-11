/* global jest, test */
import React from 'react';
import { render } from '@testing-library/react';
import LinkPicker from '../LinkPicker';

function makeProps(obj = {}) {
  return {
    types: { phone: { key: 'phone', title: 'Phone', icon: 'font-icon-phone' } },
    onModalSuccess: () => {},
    onModalClosed: () => {},
    ...obj
  };
}

test('LinkPickerMenu render() should display toggle if can create', () => {
  const { container } = render(<LinkPicker {...makeProps({
    canCreate: true
  })}
  />);
  expect(container.querySelectorAll('.link-picker__menu-toggle')).toHaveLength(1);
  expect(container.querySelectorAll('.link-picker__cannot-create')).toHaveLength(0);
});

test('LinkPickerMenu render() should display cannot create message if cannot create', () => {
  const { container } = render(<LinkPicker {...makeProps({
    canCreate: false
  })}
  />);
  expect(container.querySelectorAll('.link-picker__menu-toggle')).toHaveLength(0);
  expect(container.querySelectorAll('.link-picker__cannot-create')).toHaveLength(1);
});

test('LinkPickerMenu render() should display link type icon if can create', () => {
  const { container } = render(<LinkPicker {...makeProps({
    canCreate: true
  })}
  />);
  expect(container.querySelectorAll('.link-picker__menu-icon.font-icon-phone')).toHaveLength(1);
});
