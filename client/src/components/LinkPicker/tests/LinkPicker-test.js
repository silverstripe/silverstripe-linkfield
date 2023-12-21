/* global jest, test */
import React from 'react';
import { render } from '@testing-library/react';
import LinkPicker from '../LinkPicker';

function makeProps(obj = {}) {
  return {
    types: [{ key: 'phone', title: 'Phone' }],
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
