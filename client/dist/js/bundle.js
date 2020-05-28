/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./client/src/bundles/bundle.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./client/src/boot/index.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _Config = __webpack_require__(6);

var _Config2 = _interopRequireDefault(_Config);

var _registerReducers = __webpack_require__("./client/src/boot/registerReducers.js");

var _registerReducers2 = _interopRequireDefault(_registerReducers);

var _registerComponents = __webpack_require__("./client/src/boot/registerComponents.js");

var _registerComponents2 = _interopRequireDefault(_registerComponents);

var _registerQueries = __webpack_require__("./client/src/boot/registerQueries.js");

var _registerQueries2 = _interopRequireDefault(_registerQueries);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

document.addEventListener('DOMContentLoaded', function () {
  (0, _registerComponents2.default)();

  (0, _registerQueries2.default)();

  (0, _registerReducers2.default)();
});

/***/ }),

/***/ "./client/src/boot/registerComponents.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(0);

var _Injector2 = _interopRequireDefault(_Injector);

var _LinkPicker = __webpack_require__("./client/src/components/LinkPicker/LinkPicker.js");

var _LinkPicker2 = _interopRequireDefault(_LinkPicker);

var _LinkField = __webpack_require__("./client/src/components/LinkField/LinkField.js");

var _LinkField2 = _interopRequireDefault(_LinkField);

var _LinkModal = __webpack_require__("./client/src/components/LinkModal/LinkModal.js");

var _LinkModal2 = _interopRequireDefault(_LinkModal);

var _FileLinkModal = __webpack_require__("./client/src/components/LinkModal/FileLinkModal.js");

var _FileLinkModal2 = _interopRequireDefault(_FileLinkModal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var registerComponents = function registerComponents() {
  _Injector2.default.component.registerMany({
    LinkPicker: _LinkPicker2.default,
    LinkField: _LinkField2.default,
    'LinkModal.FormBuilderModal': _LinkModal2.default,
    'LinkModal.InsertMediaModal': _FileLinkModal2.default
  });
};

exports.default = registerComponents;

/***/ }),

/***/ "./client/src/boot/registerQueries.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(0);

var _Injector2 = _interopRequireDefault(_Injector);

var _readLinkTypes = __webpack_require__("./client/src/state/linkTypes/readLinkTypes.js");

var _readLinkTypes2 = _interopRequireDefault(_readLinkTypes);

var _readLinkDescription = __webpack_require__("./client/src/state/linkDescription/readLinkDescription.js");

var _readLinkDescription2 = _interopRequireDefault(_readLinkDescription);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var registerQueries = function registerQueries() {
  _Injector2.default.query.register('readLinkTypes', _readLinkTypes2.default);
  _Injector2.default.query.register('readLinkDescription', _readLinkDescription2.default);
};
exports.default = registerQueries;

/***/ }),

/***/ "./client/src/boot/registerReducers.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(0);

var _Injector2 = _interopRequireDefault(_Injector);

var _redux = __webpack_require__(8);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var registerReducers = function registerReducers() {};

exports.default = registerReducers;

/***/ }),

/***/ "./client/src/bundles/bundle.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__("./client/src/boot/index.js");
__webpack_require__("./client/src/entwine/JsonField.js");

/***/ }),

/***/ "./client/src/components/LinkField/LinkField.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _reactRedux = __webpack_require__(7);

var _redux = __webpack_require__(8);

var _reactApollo = __webpack_require__(13);

var _Injector = __webpack_require__(0);

var _FieldHolder = __webpack_require__(9);

var _FieldHolder2 = _interopRequireDefault(_FieldHolder);

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

var LinkField = function LinkField(_ref) {
  var id = _ref.id,
      loading = _ref.loading,
      Loading = _ref.Loading,
      data = _ref.data,
      LinkPicker = _ref.LinkPicker,
      onChange = _ref.onChange,
      types = _ref.types,
      linkDescription = _ref.linkDescription,
      props = _objectWithoutProperties(_ref, ['id', 'loading', 'Loading', 'data', 'LinkPicker', 'onChange', 'types', 'linkDescription']);

  if (loading) {
    return _react2.default.createElement(Loading, null);
  }

  var _useState = (0, _react.useState)(false),
      _useState2 = _slicedToArray(_useState, 2),
      editing = _useState2[0],
      setEditing = _useState2[1];

  var _useState3 = (0, _react.useState)(''),
      _useState4 = _slicedToArray(_useState3, 2),
      newTypeKey = _useState4[0],
      setNewTypeKey = _useState4[1];

  var onClear = function onClear(event) {
    typeof onChange === 'function' && onChange(event, { id: id, value: {} });
  };

  var typeKey = data.typeKey;

  var type = types[typeKey];
  var modalType = newTypeKey ? types[newTypeKey] : type;

  var linkProps = {
    title: data ? data.Title : '',
    link: type ? { type: type, title: data.Title, description: linkDescription } : undefined,
    onEdit: function onEdit() {
      setEditing(true);
    },
    onClear: onClear,
    onSelect: function onSelect(key) {
      setNewTypeKey(key);
      setEditing(true);
    },
    types: Object.values(types)
  };

  var onModalSubmit = function onModalSubmit(data, action, submitFn) {
    var SecurityID = data.SecurityID,
        action_insert = data.action_insert,
        value = _objectWithoutProperties(data, ['SecurityID', 'action_insert']);

    typeof onChange === 'function' && onChange(event, { id: id, value: value });
    setEditing(false);
    setNewTypeKey('');
    return Promise.resolve();
  };

  var modalProps = {
    type: modalType,
    editing: editing,
    onSubmit: onModalSubmit,
    onClosed: function onClosed() {
      setEditing(false);
    },
    data: data
  };

  var handlerName = modalType ? modalType.handlerName : 'FormBuilderModal';
  var LinkModal = (0, _Injector.loadComponent)('LinkModal.' + handlerName);

  return _react2.default.createElement(
    _react.Fragment,
    null,
    _react2.default.createElement(LinkPicker, linkProps),
    _react2.default.createElement(LinkModal, modalProps)
  );
};

var stringifyData = function stringifyData(Component) {
  return function (props) {
    return _react2.default.createElement(Component, _extends({ dataStr: JSON.stringify(props.data) }, props));
  };
};

exports.default = (0, _redux.compose)((0, _Injector.inject)(['LinkPicker', 'Loading']), (0, _Injector.injectGraphql)('readLinkTypes'), stringifyData, (0, _Injector.injectGraphql)('readLinkDescription'), _reactApollo.withApollo, _FieldHolder2.default)(LinkField);

/***/ }),

/***/ "./client/src/components/LinkModal/FileLinkModal.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _InsertMediaModal = __webpack_require__(11);

var _InsertMediaModal2 = _interopRequireDefault(_InsertMediaModal);

var _reactRedux = __webpack_require__(7);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

var FileLinkModal = function FileLinkModal(_ref) {
  var type = _ref.type,
      editing = _ref.editing,
      data = _ref.data,
      actions = _ref.actions,
      onSubmit = _ref.onSubmit,
      props = _objectWithoutProperties(_ref, ['type', 'editing', 'data', 'actions', 'onSubmit']);

  if (!type) {
    return false;
  }

  (0, _react.useEffect)(function () {
    if (editing) {
      actions.initModal();
    } else {
      actions.reset();
    }
  }, [editing]);

  var attrs = data ? {
    ID: data.FileID,
    Description: data.Title,
    TargetBlank: data.OpenInNew ? true : false
  } : {};

  var onInsert = function onInsert(_ref2) {
    var ID = _ref2.ID,
        Description = _ref2.Description,
        TargetBlank = _ref2.TargetBlank;

    return onSubmit({
      FileID: ID,
      Title: Description,
      OpenInNew: TargetBlank,
      typeKey: type.key
    }, '', function () {});
  };

  return _react2.default.createElement(_InsertMediaModal2.default, _extends({
    isOpen: editing,
    type: 'insert-link',
    title: false,
    bodyClassName: 'modal__dialog',
    className: 'insert-link__dialog-wrapper--internal',
    fileAttributes: attrs,
    onInsert: onInsert
  }, props));
};

function mapStateToProps() {
  return {};
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      initModal: function initModal() {
        return dispatch({
          type: 'INIT_FORM_SCHEMA_STACK',
          payload: { formSchema: { type: 'insert-link', nextType: 'admin' } }
        });
      },
      reset: function reset() {
        return dispatch({ type: 'RESET' });
      }
    }
  };
}

exports.default = (0, _reactRedux.connect)(mapStateToProps, mapDispatchToProps)(FileLinkModal);

/***/ }),

/***/ "./client/src/components/LinkModal/LinkModal.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _FormBuilderModal = __webpack_require__(10);

var _FormBuilderModal2 = _interopRequireDefault(_FormBuilderModal);

var _url = __webpack_require__(12);

var _url2 = _interopRequireDefault(_url);

var _qs = __webpack_require__(16);

var _qs2 = _interopRequireDefault(_qs);

var _Config = __webpack_require__(6);

var _Config2 = _interopRequireDefault(_Config);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

var leftAndMain = 'SilverStripe\\Admin\\LeftAndMain';

var buildSchemaUrl = function buildSchemaUrl(key, data) {
  var schemaUrl = _Config2.default.getSection(leftAndMain).form.DynamicLink.schemaUrl;

  var parsedURL = _url2.default.parse(schemaUrl);
  var parsedQs = _qs2.default.parse(parsedURL.query);
  parsedQs.key = key;
  if (data) {
    parsedQs.data = JSON.stringify(data);
  }
  return _url2.default.format(_extends({}, parsedURL, { search: _qs2.default.stringify(parsedQs) }));
};

var LinkModal = function LinkModal(_ref) {
  var type = _ref.type,
      editing = _ref.editing,
      data = _ref.data,
      props = _objectWithoutProperties(_ref, ['type', 'editing', 'data']);

  if (!type) {
    return false;
  }

  return _react2.default.createElement(_FormBuilderModal2.default, _extends({
    title: type.title,
    isOpen: editing,
    schemaUrl: buildSchemaUrl(type.key, data),
    identifier: 'Link.EditingLinkInfo'
  }, props));
};

exports.default = LinkModal;

/***/ }),

/***/ "./client/src/components/LinkPicker/LinkPicker.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Component = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _Injector = __webpack_require__(0);

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactstrap = __webpack_require__(4);

var _classnames = __webpack_require__(5);

var _classnames2 = _interopRequireDefault(_classnames);

var _LinkPickerMenu = __webpack_require__("./client/src/components/LinkPicker/LinkPickerMenu.js");

var _LinkPickerMenu2 = _interopRequireDefault(_LinkPickerMenu);

var _LinkPickerTitle = __webpack_require__("./client/src/components/LinkPicker/LinkPickerTitle.js");

var _LinkPickerTitle2 = _interopRequireDefault(_LinkPickerTitle);

var _LinkType = __webpack_require__("./client/src/types/LinkType.js");

var _LinkType2 = _interopRequireDefault(_LinkType);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var LinkPicker = function LinkPicker(_ref) {
  var types = _ref.types,
      onSelect = _ref.onSelect,
      link = _ref.link,
      onEdit = _ref.onEdit,
      onClear = _ref.onClear;
  return _react2.default.createElement(
    'div',
    {
      className: (0, _classnames2.default)('link-picker', 'form-control', { 'link-picker--selected': link }) },
    link === undefined && _react2.default.createElement(_LinkPickerMenu2.default, { types: types, onSelect: onSelect }),
    link && _react2.default.createElement(_LinkPickerTitle2.default, _extends({}, link, { onClear: onClear, onClick: function onClick() {
        return link && onEdit && onEdit(link);
      } }))
  );
};

LinkPicker.propTypes = _extends({}, _LinkPickerMenu2.default.propTypes, {
  link: _propTypes2.default.shape(_LinkPickerTitle2.default.propTypes),
  onEdit: _propTypes2.default.func,
  onClear: _propTypes2.default.func
});

exports.Component = LinkPicker;
exports.default = LinkPicker;

/***/ }),

/***/ "./client/src/components/LinkPicker/LinkPickerMenu.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _Injector = __webpack_require__(0);

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactstrap = __webpack_require__(4);

var _classnames = __webpack_require__(5);

var _classnames2 = _interopRequireDefault(_classnames);

var _LinkType = __webpack_require__("./client/src/types/LinkType.js");

var _LinkType2 = _interopRequireDefault(_LinkType);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var LinkPickerMenu = function LinkPickerMenu(_ref) {
  var types = _ref.types,
      onSelect = _ref.onSelect;

  var _useState = (0, _react.useState)(false),
      _useState2 = _slicedToArray(_useState, 2),
      isOpen = _useState2[0],
      setIsOpen = _useState2[1];

  var toggle = function toggle() {
    return setIsOpen(function (prevState) {
      return !prevState;
    });
  };

  return _react2.default.createElement(
    _reactstrap.Dropdown,
    {
      isOpen: isOpen,
      toggle: toggle,
      className: 'link-picker__menu'
    },
    _react2.default.createElement(
      _reactstrap.DropdownToggle,
      { className: 'link-picker__menu-toggle font-icon-link', caret: true },
      _i18n2.default._t('Link.ADD_LINK', 'Add Link')
    ),
    _react2.default.createElement(
      _reactstrap.DropdownMenu,
      null,
      types.map(function (_ref2) {
        var key = _ref2.key,
            title = _ref2.title;
        return _react2.default.createElement(
          _reactstrap.DropdownItem,
          { key: key, onClick: function onClick() {
              return onSelect(key);
            } },
          title
        );
      })
    )
  );
};

LinkPickerMenu.propTypes = {
  types: _propTypes2.default.arrayOf(_LinkType2.default).isRequired,
  onSelect: _propTypes2.default.func.isRequired
};

exports.default = LinkPickerMenu;

/***/ }),

/***/ "./client/src/components/LinkPicker/LinkPickerTitle.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _i18n = __webpack_require__(3);

var _i18n2 = _interopRequireDefault(_i18n);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _Injector = __webpack_require__(0);

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

var _classnames = __webpack_require__(5);

var _classnames2 = _interopRequireDefault(_classnames);

var _LinkType = __webpack_require__("./client/src/types/LinkType.js");

var _LinkType2 = _interopRequireDefault(_LinkType);

var _reactstrap = __webpack_require__(4);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var stopPropagation = function stopPropagation(fn) {
  return function (e) {
    console.log('trying to stop propagation');
    e.nativeEvent.stopImmediatePropagation();
    e.preventDefault();
    e.nativeEvent.preventDefault();
    e.stopPropagation();
    fn && fn();
  };
};

var LinkPickerTitle = function LinkPickerTitle(_ref) {
  var title = _ref.title,
      type = _ref.type,
      description = _ref.description,
      onClear = _ref.onClear,
      onClick = _ref.onClick;
  return _react2.default.createElement(
    _reactstrap.Button,
    { className: 'link-picker__link font-icon-link', color: 'secondary', onClick: stopPropagation(onClick) },
    _react2.default.createElement(
      'div',
      { className: 'link-picker__link-detail' },
      _react2.default.createElement(
        'div',
        { className: 'link-picker__title' },
        title
      ),
      _react2.default.createElement(
        'small',
        { className: 'link-picker__type' },
        type.title,
        ':\xA0',
        _react2.default.createElement(
          'span',
          { className: 'link-picker__url' },
          description
        )
      )
    ),
    _react2.default.createElement(
      _reactstrap.Button,
      { className: 'link-picker__clear', color: 'link', onClick: stopPropagation(onClear) },
      _i18n2.default._t('Link.CLEAR', 'Clear')
    )
  );
};

LinkPickerTitle.propTypes = {
  title: _propTypes2.default.string.isRequired,
  type: _LinkType2.default,
  description: _propTypes2.default.string,
  onClear: _propTypes2.default.func,
  onClick: _propTypes2.default.func
};

exports.default = LinkPickerTitle;

/***/ }),

/***/ "./client/src/entwine/JsonField.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _jquery = __webpack_require__(15);

var _jquery2 = _interopRequireDefault(_jquery);

var _react = __webpack_require__(1);

var _react2 = _interopRequireDefault(_react);

var _reactDom = __webpack_require__(14);

var _reactDom2 = _interopRequireDefault(_reactDom);

var _Injector = __webpack_require__(0);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jquery2.default.entwine('ss', function ($) {
  $('.js-injector-boot .entwine-jsonfield').entwine({

    Component: null,

    onmatch: function onmatch() {
      var cmsContent = this.closest('.cms-content').attr('id');
      var context = cmsContent ? { context: cmsContent } : {};

      var schemaComponent = this.data('schema-component');
      var ReactField = (0, _Injector.loadComponent)(schemaComponent, context);

      this.setComponent(ReactField);
      this._super();
      this.refresh();
    },
    refresh: function refresh() {
      var props = this.getProps();
      var ReactField = this.getComponent();
      _reactDom2.default.render(_react2.default.createElement(ReactField, _extends({}, props, { noHolder: true })), this[0]);
    },
    handleChange: function handleChange(event, _ref) {
      var id = _ref.id,
          value = _ref.value;

      var fieldID = $(this).data('field-id');
      $('#' + fieldID).val(JSON.stringify(value)).trigger('change');
      this.refresh();
    },
    getProps: function getProps() {
      var fieldID = $(this).data('field-id');
      var dataStr = $('#' + fieldID).val();
      var data = dataStr ? JSON.parse(dataStr) : undefined;

      return {
        id: fieldID,
        data: data,
        onChange: this.handleChange.bind(this)
      };
    },
    onunmatch: function onunmatch() {
      _reactDom2.default.unmountComponentAtNode(this[0]);
    }
  });
});

/***/ }),

/***/ "./client/src/state/linkDescription/readLinkDescription.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _Injector = __webpack_require__(0);

var apolloConfig = {
  props: function props(_props) {
    var _props$data = _props.data,
        error = _props$data.error,
        readLinkDescription = _props$data.readLinkDescription,
        networkLoading = _props$data.loading;

    var errors = error && error.graphQLErrors && error.graphQLErrors.map(function (graphQLError) {
      return graphQLError.message;
    });
    var linkDescription = readLinkDescription ? readLinkDescription.description : '';

    return {
      loading: networkLoading,
      linkDescription: linkDescription,
      graphQLErrors: errors
    };
  }
};

var READ = _Injector.graphqlTemplates.READ;

var query = {
  apolloConfig: apolloConfig,
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
  fields: ['description']
};
exports.default = query;

/***/ }),

/***/ "./client/src/state/linkTypes/readLinkTypes.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _Injector = __webpack_require__(0);

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var apolloConfig = {
  props: function props(_props) {
    var _props$data = _props.data,
        error = _props$data.error,
        readLinkTypes = _props$data.readLinkTypes,
        networkLoading = _props$data.loading;

    var errors = error && error.graphQLErrors && error.graphQLErrors.map(function (graphQLError) {
      return graphQLError.message;
    });

    var types = readLinkTypes ? readLinkTypes.reduce(function (accumulator, type) {
      return _extends({}, accumulator, _defineProperty({}, type.key, type));
    }, {}) : {};

    return {
      loading: networkLoading,
      types: types,
      graphQLErrors: errors
    };
  }
};

var READ = _Injector.graphqlTemplates.READ;

var query = {
  apolloConfig: apolloConfig,
  templateName: READ,
  pluralName: 'LinkTypes',
  pagination: false,
  params: {
    keys: '[ID]'
  },
  args: {
    root: {
      keys: 'keys'
    }
  },
  fields: ['key', 'title', 'handlerName']
};
exports.default = query;

/***/ }),

/***/ "./client/src/types/LinkType.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});

var _propTypes = __webpack_require__(2);

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var LinkType = _propTypes2.default.shape({
  key: _propTypes2.default.string.isRequired,
  title: _propTypes2.default.string.isRequired
});

exports.default = LinkType;

/***/ }),

/***/ 0:
/***/ (function(module, exports) {

module.exports = Injector;

/***/ }),

/***/ 1:
/***/ (function(module, exports) {

module.exports = React;

/***/ }),

/***/ 10:
/***/ (function(module, exports) {

module.exports = FormBuilderModal;

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

module.exports = InsertMediaModal;

/***/ }),

/***/ 12:
/***/ (function(module, exports) {

module.exports = NodeUrl;

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

module.exports = ReactApollo;

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

module.exports = ReactDom;

/***/ }),

/***/ 15:
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ }),

/***/ 16:
/***/ (function(module, exports) {

module.exports = qs;

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

module.exports = PropTypes;

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

module.exports = i18n;

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

module.exports = Reactstrap;

/***/ }),

/***/ 5:
/***/ (function(module, exports) {

module.exports = classnames;

/***/ }),

/***/ 6:
/***/ (function(module, exports) {

module.exports = Config;

/***/ }),

/***/ 7:
/***/ (function(module, exports) {

module.exports = ReactRedux;

/***/ }),

/***/ 8:
/***/ (function(module, exports) {

module.exports = Redux;

/***/ }),

/***/ 9:
/***/ (function(module, exports) {

module.exports = FieldHolder;

/***/ })

/******/ });
//# sourceMappingURL=bundle.js.map