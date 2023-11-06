
/* eslint-disable */
import Injector from 'lib/Injector';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkField from 'components/LinkField/LinkField';
import LinkModal from 'components/LinkModal/LinkModal';
import FileLinkModal from 'components/LinkModal/FileLinkModal';

const registerComponents = () => {
  Injector.component.registerMany({
    LinkPicker,
    LinkField,
    'LinkModal.FormBuilderModal': LinkModal,
    'LinkModal.InsertMediaModal': FileLinkModal
  });
};

export default registerComponents;
