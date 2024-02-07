import React from 'react';
import LinkPickerTitle from 'components/LinkPicker/LinkPickerTitle';
import { LinkFieldContext } from '../LinkField/LinkField';

// mock toast actions
const mockedActions = {
  toasts: {
    error: () => {},
    success: () => {},
  }
};

export default {
  title: 'LinkField/LinkPicker/LinkPickerTitle',
  component: LinkPickerTitle,
  tags: ['autodocs'],
  parameters: {
    docs: {
      description: {
        component: 'The LinkPickerTitle component. Used to display a link inside the link field'
      },
      canvas: {
        sourceState: 'shown',
      },
      controls: {
        exclude: [
          'id',
          'onDelete',
          'onClick',
          'onUnpublishedVersionedState',
          'isFirst',
          'isLast',
          'isSorting',
          'canCreate',
        ],
      },
    },
  },
  argTypes: {
    versionState: {
      description: 'The current versioned state of the link. "unsaved" and "unversioned" are effectively identical.',
      control: 'select',
      options: ['unversioned', 'unsaved', 'published', 'draft', 'modified'],
    },
    title: {
      description: 'The title (aka link text) for the link.',
    },
    typeTitle: {
      description: 'Text that informs the user what type of link this is.',
    },
    description: {
      description: 'The URL, or information about what the link is linking to.',
    },
    typeIcon: {
      description: 'CSS class of an icon for this type of link (usually prefixed with "font-icon-"). See the Admin/Icons story for the full set of options.',
    },
    isMulti: {
      description: 'Whether this link is inside a link field that supports multiple links or not.',
    },
    canDelete: {
      description: 'Whether the current user has the permissions necessary to delete (or archive) this link.',
    },
    readonly: {
      description: 'Whether the link field is readonly.',
    },
    disabled: {
      description: 'Whether the link field is disabled.',
    },
    loading: {
      description: 'Whether the link field is loading. This is passed as part of the context, not as a prop, but is here for demonstration purposes.',
    },
  },
};

export const _LinkPickerTitle = {
  name: 'LinkPickerTitle',
  args: {
    id: 1,
    title: 'Example link',
    typeTitle: 'External URL',
    description: 'https://www.example.com',
    typeIcon: 'font-icon-external-link',
    versionState: 'unversioned',
    onDelete: () => {},
    onClick: () => {},
    onUnpublishedVersionedState: () => {},
    isFirst: true,
    isLast: true,
    isMulti: false,
    canCreate: true,
    canDelete: true,
    isSorting: false,
    readonly: false,
    disabled: false,
    loading: false,
  },
  render: (args) => {
    const providerArgs = {
      ownerID: 1,
      ownerClass: '',
      ownerRelation: '',
      actions: mockedActions,
      loading: args.loading,
    };
    delete args.loading;
    return <LinkFieldContext.Provider value={providerArgs}>
      <LinkPickerTitle {...args} />
    </LinkFieldContext.Provider>;
  }
};
