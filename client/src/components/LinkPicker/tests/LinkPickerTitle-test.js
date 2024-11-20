/* global jest, test */

import React, { createRef } from 'react';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { LinkFieldContext } from 'components/LinkField/LinkField';
import LinkPickerTitle from '../LinkPickerTitle';

function makeProps(obj = {}) {
  return {
    id: 1,
    title: 'My title',
    description: 'My description',
    versionState: 'draft',
    statusFlags: { draft: 'Draft' },
    typeTitle: 'Phone',
    typeIcon: 'font-icon-phone',
    canDelete: true,
    canCreate: true,
    readonly: false,
    disabled: false,
    onDelete: () => {},
    onClick: () => {},
    onUnpublishedVersionedState: () => {},
    isMulti: false,
    isFirst: false,
    isLast: false,
    isSorting: false,
    buttonRef: createRef(),
    ...obj
  };
}

test('LinkPickerTitle render() should display link type title and link type icon', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__title')).toHaveLength(1);
  expect(container.querySelector('.link-picker__title')).toHaveTextContent('My title');
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
  expect(container.querySelector('.link-picker__type')).toHaveTextContent('Phone');
  expect(container.querySelector('.link-picker__url')).toHaveTextContent('My description');
  expect(container.querySelector('.link-picker__title > .badge')).toHaveTextContent('Draft');
  expect(container.querySelectorAll('.link-picker__title > .status-draft')).toHaveLength(1);
});

test('LinkPickerTitle render() should display clear button if can delete', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(1);
  expect(container.querySelector('.link-picker__delete')).toHaveTextContent('Archive');
  expect(container.querySelector('.link-picker__delete').getAttribute('aria-label')).toBe('Archive');
  expect(container.querySelectorAll('.font-icon-phone')).toHaveLength(1);
});

test('LinkPickerTitle render() should not display clear button if cannot delete', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ canDelete: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
});

test('LinkPickerTitle render() should not display clear button if readonly', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ readonly: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
});

test('LinkPickerTitle render() should not display clear button if disabled', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ readonly: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__delete')).toHaveLength(0);
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
  userEvent.click(container.querySelector('.link-picker__delete'));
  await waitFor(() => {
    expect(mockOnDelete).toHaveBeenCalledTimes(1);
  });
});

test('LinkPickerTitle delete button should not fire the onDelete callback while loading', async () => {
  const mockOnDelete = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: true }}>
    <LinkPickerTitle {...makeProps({
      canDelete: true,
      onDelete: mockOnDelete,
    })}
    />
  </LinkFieldContext.Provider>);
  userEvent.click(container.querySelector('.link-picker__delete'));
  await waitFor(() => {
    expect(mockOnDelete).toHaveBeenCalledTimes(0);
  });
});

test('LinkPickerTitle main button should fire the onClick callback when not loading', async () => {
  const mockOnClick = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ onClick: mockOnClick })} />
  </LinkFieldContext.Provider>);
  userEvent.click(container.querySelector('.link-picker__button'));
  await waitFor(() => {
    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });
});

test('LinkPickerTitle main button should not fire the onClick callback while loading', async () => {
  const mockOnClick = jest.fn();
  const { container } = render(<LinkFieldContext.Provider value={{ loading: true }}>
    <LinkPickerTitle {...makeProps({ onClick: mockOnClick })} />
  </LinkFieldContext.Provider>);
  userEvent.click(container.querySelector('.link-picker__button'));
  await waitFor(() => {
    expect(mockOnClick).toHaveBeenCalledTimes(0);
  });
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

test('LinkPickerTitle render() should have disabled class if set to disabled', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__link--disabled')).toHaveLength(1);
});

test('LinkPickerTitle render() should not have disabled class if set to disabled', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__link--disabled')).toHaveLength(0);
});

test('dnd handler is displayed on LinkPickerTitle on MultiLinkField', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: false, readonly: false, isMulti: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__drag-handle')).toHaveLength(1);
});

test('dnd handler is not displayed if link field is disabled', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: true, readonly: false, isMulti: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__drag-handle')).toHaveLength(0);
});

test('dnd handler is not displayed if link field is readonly', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: false, readonly: true, isMulti: true })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__drag-handle')).toHaveLength(0);
});

test('dnd handler is not displayed if link field is not MultiLinkField', () => {
  const { container } = render(<LinkFieldContext.Provider value={{ loading: false }}>
    <LinkPickerTitle {...makeProps({ disabled: false, readonly: false, isMulti: false })} />
  </LinkFieldContext.Provider>);
  expect(container.querySelectorAll('.link-picker__drag-handle')).toHaveLength(0);
});
