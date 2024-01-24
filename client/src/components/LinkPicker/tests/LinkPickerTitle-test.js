/* global jest, test */

import React from 'react';
import { render, fireEvent } from '@testing-library/react';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkPickerTitle from '../LinkPickerTitle';

function makeProps(obj = {}) {
  return {
    id: 1,
    title: 'My title',
    description: 'My description',
    versionState: 'draft',
    typeTitle: 'Phone',
    typeIcon: 'font-icon-phone',
    canDelete: true,
    canCreate: true,
    readonly: false,
    onDelete: () => {},
    onClick: () => {},
    isMulti: false,
    isFirst: false,
    isLast: false,
    isSorting: false,
    ...obj
  };
}

test('LinkPickerTitle render() should display clear button if can delete', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(1);
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
});

test('LinkPickerTitle render() should not display clear button if cannot delete', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
});

test('LinkPickerTitle render() should display link type icon', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
});

test('LinkPickerTitle delete button should fire the onDelete callback when not loading', async () => {
  const mockOnDelete = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({
      canDelete: true,
      onDelete: mockOnDelete,
    })}
    />
  </LinkFieldContext.Provider>);
  fireEvent.click(container.querySelector('button.link-picker__delete'));
  expect(mockOnDelete).toHaveBeenCalledTimes(1);
});

test('LinkPickerTitle delete button should not fire the onDelete callback while loading', () => {
  const mockOnDelete = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: true }}>
    <LinkPickerTitle {...makeProps({
      canDelete: true,
      onDelete: mockOnDelete,
    })}
    />
  </LinkFieldContext.Provider>);
  fireEvent.click(container.querySelector('button.link-picker__delete'));
  expect(mockOnDelete).toHaveBeenCalledTimes(0);
});

test('LinkPickerTitle main button should fire the onClick callback when not loading', async () => {
  const mockOnClick = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ onClick: mockOnClick })} />
  </LinkFieldContext.Provider>);
  fireEvent.click(container.querySelector('button.link-picker__button'));
  expect(mockOnClick).toHaveBeenCalledTimes(1);
});

test('LinkPickerTitle main button should not fire the onClick callback while loading', async () => {
  const mockOnClick = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: true }}>
    <LinkPickerTitle {...makeProps({ onClick: mockOnClick })} />
  </LinkFieldContext.Provider>);
  fireEvent.click(container.querySelector('button.link-picker__button'));
  expect(mockOnClick).toHaveBeenCalledTimes(0);
});

test('LinkPickerTitle render() should have readonly class if set to readonly', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ readonly: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__link--readonly')).toHaveLength(1);
});

test('LinkPickerTitle render() should not have readonly class if set to readonly', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ readonly: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__link--readonly')).toHaveLength(0);
});
