/* global jest, test */

import React from 'react';
import { render } from '@testing-library/react';
import LinkPickerTitle from '../LinkPickerTitle';

function makeProps(obj = {}) {
  return {
    id: 1,
    title: 'My title',
    description: 'My description',
    versionState: 'draft',
    typeTitle: 'Phone',
    typeIcon: 'font-icon-phone',
    onDelete: () => {},
    onClick: () => {},
    ...obj
  };
}

test('LinkPickerTitle render() should display clear button if can delete', () => {
  const { container } = render(<LinkPickerTitle {...makeProps({
    canDelete: true
  })}
  />);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(1);
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
});

test('LinkPickerTitle render() should not display clear button if cannot delete', () => {
  const { container } = render(<LinkPickerTitle {...makeProps({
    canDelete: false
  })}
  />);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
});

test('LinkPickerTitle render() should display link type icon', () => {
  const { container } = render(<LinkPickerTitle {...makeProps({
    canDelete: false
  })}
  />);
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
});
