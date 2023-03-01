/* eslint-disable */
import { graphqlTemplates } from 'lib/Injector';

const apolloConfig = {
  props(
    props
  ) {
    const {
      data: {
        error,
        readLinkDescription,
        loading: networkLoading,
      },
    } = props;
    const errors = error && error.graphQLErrors &&
      error.graphQLErrors.map((graphQLError) => graphQLError.message);
    const linkDescription = readLinkDescription ? readLinkDescription.description : '';

    return {
      loading: networkLoading,
      linkDescription,
      graphQLErrors: errors,
    };
  },
};

const { READ } = graphqlTemplates;
const query = {
  apolloConfig,
  templateName: READ,
  pluralName: 'LinkDescription',
  pagination: false,
  params: {
    dataStr: 'String!'
  },
  args: {
    root: {
      dataStr: 'dataStr'
    }
  },
  fields: ['description'],
};
export default query;
