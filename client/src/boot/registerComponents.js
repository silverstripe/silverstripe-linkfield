import Injector from 'lib/Injector';
import LinkPicker from 'components/LinkPicker/LinkPicker';
import LinkField from 'components/LinkField/LinkField';


const registerComponents = () => {
  Injector.component.registerMany({
    LinkPicker,
    LinkField
  });
};

export default registerComponents;
