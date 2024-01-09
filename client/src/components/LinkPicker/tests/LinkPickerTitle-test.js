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
});

test('LinkPickerTitle render() should not display clear button if cannot delete', () => {
  const { container } = render(<LinkPickerTitle {...makeProps({
    canDelete: false
  })}
  />);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
});
