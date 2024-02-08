/* global window */
import React from 'react';
import { Component as LinkField } from 'components/LinkField/LinkField';

// mock global ss config
if (!window.ss) {
  window.ss = {};
}
if (!window.ss.config) {
  window.ss.config = {
    sections: [
      {
        name: 'SilverStripe\\LinkField\\Controllers\\LinkFieldController',
        form: {
          linkForm: {
            dataUrl: '',
          }
        }
      },
    ]
  };
}

// mock toast actions
const mockedActions = {
  toasts: {
    error: () => {},
    success: () => {},
  }
};

// predetermine link types
const linkTypes = {
  sitetree: {
    key: 'sitetree',
    title: 'Page on this site',
    handlerName: 'FormBuilderModal',
    priority: 0,
    icon: 'font-icon-page',
    allowed: true
  },
  file: {
    key: 'file',
    title: 'Link to a file',
    handlerName: 'FormBuilderModal',
    priority: 10,
    icon: 'font-icon-image',
    allowed: true
  },
  external: {
    key: 'external',
    title: 'Link to external URL',
    handlerName: 'FormBuilderModal',
    priority: 20,
    icon: 'font-icon-external-link',
    allowed: true
  },
  email: {
    key: 'email',
    title: 'Link to email address',
    handlerName: 'FormBuilderModal',
    priority: 30,
    icon: 'font-icon-p-mail',
    allowed: true
  },
  phone: {
    key: 'phone',
    title: 'Phone number',
    handlerName: 'FormBuilderModal',
    priority: 40,
    icon: 'font-icon-mobile',
    allowed: true
  }
};

export default {
  title: 'Linkfield/LinkField',
  component: LinkField,
  tags: ['autodocs'],
  parameters: {
    docs: {
      description: {
        component: 'The LinkField component. Note that the form modal for creating, editing, and viewing link data is disabled for the storybook.'
      },
      canvas: {
        sourceState: 'shown',
      },
      controls: {
        exclude: [
          'onChange',
          'value',
          'ownerID',
          'ownerClass',
          'ownerRelation',
          'actions',
        ],
      },
    },
  },
  argTypes: {
    types: {
      description: 'Types of links that are allowed in this field. The actual prop is a JSON object with some metadata about each type.',
      control: 'inline-check',
      options: Object.keys(linkTypes),
    },
    isMulti: {
      description: 'Whether the field supports multiple links or not.',
    },
    canCreate: {
      description: 'Whether the current user has create permission or not.',
    },
    readonly: {
      description: 'Whether the field is readonly or not.',
    },
    disabled: {
      description: 'Whether the field is disabled or not.',
    },
    ownerSaved: {
      description: 'Whether the record which owns the link field has been saved or not. The actual props for this are OwnerID, OwnerClass, and OwnerRelation.',
    },
  },
};

export const _LinkField = {
  name: 'LinkField',
  args: {
    value: [0],
    onChange: () => {},
    types: Object.keys(linkTypes),
    actions: mockedActions,
    isMulti: false,
    canCreate: true,
    readonly: false,
    disabled: false,
    ownerSaved: true,
    ownerClass: '',
    ownerRelation: '',
  },
  render: (args) => {
    const { types, ownerSaved } = args;
    delete args.ownerSaved;
    delete args.hasLinks;

    // `types` must be an array in args so controls can be used to toggle them.
    // Because of that, we need to turn that back into the JSON object before
    // passing that prop.
    args.types = {};
    types.sort((a, b) => linkTypes[a].priority - linkTypes[b].priority);
    // eslint-disable-next-line no-restricted-syntax
    for (const type of types) {
      args.types[type] = linkTypes[type];
    }

    // Determine whether the link is rendered as though the parent record is saved or not
    args.ownerID = ownerSaved ? 1 : 0;

    return <LinkField {...args} />;
  },
};
