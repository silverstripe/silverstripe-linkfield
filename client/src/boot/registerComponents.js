/* eslint-disable */
import Injector from 'lib/Injector';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkField from 'components/LinkField/LinkField';
import LinkModal from 'components/LinkModal/LinkModal';

const registerComponents = () => {
  Injector.component.registerMany({
    LinkPicker,
    LinkField,
    'LinkModal.FormBuilderModal': LinkModal,
  });
};

export default registerComponents;
