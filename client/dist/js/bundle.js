!function(){"use strict";var e={274:function(e,t,n){var r,o=(r=n(521))&&r.__esModule?r:{default:r};document.addEventListener("DOMContentLoaded",(()=>{(0,o.default)()}))},521:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=u(n(648)),o=u(n(809)),i=u(n(852)),l=u(n(117)),a=u(n(606));function u(e){return e&&e.__esModule?e:{default:e}}var s=()=>{r.default.component.registerMany({LinkPicker:o.default,LinkField:i.default,"LinkModal.FormBuilderModal":l.default,"LinkModal.InsertMediaModal":a.default})};t.default=s},852:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=t.LinkFieldContext=t.Component=void 0;var r=g(n(363)),o=n(827),i=n(624),l=(n(648),m(n(42))),a=m(n(809)),u=m(n(734)),s=m(n(264)),d=(m(n(686)),m(n(697))),c=g(n(123)),f=m(n(159)),p=m(n(510)),y=m(n(86)),v=m(n(754)),_=m(n(872)),k=m(n(902));function m(e){return e&&e.__esModule?e:{default:e}}function O(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(O=function(e){return e?n:t})(e)}function g(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=O(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}return r.default=e,n&&n.set(e,r),r}const b=(0,r.createContext)(null);t.LinkFieldContext=b;const h="SilverStripe\\LinkField\\Controllers\\LinkFieldController",C=e=>{var t;let{value:n=null,onChange:o,types:i={},actions:l,isMulti:c=!1,canCreate:y,ownerID:m,ownerClass:O,ownerRelation:g}=e;const[C,M]=(0,r.useState)({}),[E,R]=(0,r.useState)(0),[w,D]=(0,r.useState)(!1);let j=n;Array.isArray(j)||("number"==typeof j&&0!=j&&(j=[j]),j||(j=[])),(0,r.useEffect)((()=>{if(!E&&j.length>0){D(!0);const e=[];for(const t of j)e.push(`itemIDs[]=${t}`);const t=`${p.default.getSection(h).form.linkForm.dataUrl}?${e.join("&")}`;f.default.get(t).then((e=>e.json())).then((e=>{M(e),D(!1)})).catch((()=>{l.toasts.error(v.default._t("LinkField.FAILED_TO_LOAD_LINKS","Failed to load links")),D(!1)}))}}),[E,n&&n.length]);const I=()=>{R(0)},L=e=>{R(0);const t=[...j];t.includes(e)||t.push(e),o(c?t:t[0]),l.toasts.success(v.default._t("LinkField.SAVE_SUCCESS","Saved link"))},F=e=>{var t;let n=`${p.default.getSection(h).form.linkForm.deleteUrl}/${e}`;const r=_.default.parse(n),i=k.default.parse(r.query);i.ownerID=m,i.ownerClass=O,i.ownerRelation=g,n=_.default.format({...r,search:k.default.stringify(i)});const a=(null===(t=C[e])||void 0===t?void 0:t.versionState)||"",u=["draft","modified","published"].includes(a),s=u?v.default._t("LinkField.ARCHIVE_SUCCESS","Archived link"):v.default._t("LinkField.DELETE_SUCCESS","Deleted link"),d=u?v.default._t("LinkField.ARCHIVE_ERROR","Failed to archive link"):v.default._t("LinkField.DELETE_ERROR","Failed to delete link");f.default.delete(n,{},{"X-SecurityID":p.default.get("SecurityID")}).then((()=>l.toasts.success(s))).catch((()=>l.toasts.error(d)));const y={...C};delete y[e],M(y),o(c?Object.keys(y):0)},P=0===m,S=!P&&(c||0===Object.keys(C).length),T=!P&&Boolean(E),q=v.default._t("LinkField.SAVE_RECORD_FIRST","Cannot add links until the record has been saved");return r.default.createElement(b.Provider,{value:{ownerID:m,ownerClass:O,ownerRelation:g,actions:l,loading:w}},r.default.createElement("div",{className:"link-field__container"},P&&r.default.createElement("div",{className:"link-field__save-record-first"},q),w&&!P&&r.default.createElement(s.default,{containerClass:"link-field__loading"}),S&&r.default.createElement(a.default,{onModalSuccess:L,onModalClosed:I,types:i,canCreate:y}),r.default.createElement("div",null," ",(()=>{const e=[];for(const d of j){var t,n,o,l,a,s;if(!C[d])continue;const c=i.hasOwnProperty(null===(t=C[d])||void 0===t?void 0:t.typeKey)?i[null===(n=C[d])||void 0===n?void 0:n.typeKey]:{};e.push(r.default.createElement(u.default,{key:d,id:d,title:null===(o=C[d])||void 0===o?void 0:o.Title,description:null===(l=C[d])||void 0===l?void 0:l.description,versionState:null===(a=C[d])||void 0===a?void 0:a.versionState,typeTitle:c.title||"",typeIcon:c.icon,onDelete:F,onClick:()=>{R(d)},canDelete:!(null===(s=C[d])||void 0===s||!s.canDelete)}))}return e})()," "),T&&r.default.createElement(d.default,{types:i,typeKey:null===(t=C[E])||void 0===t?void 0:t.typeKey,isOpen:Boolean(E),onSuccess:L,onClosed:I,linkID:E})))};t.Component=C,C.propTypes={value:y.default.oneOfType([y.default.arrayOf(y.default.number),y.default.number]),onChange:y.default.func.isRequired,types:y.default.object.isRequired,actions:y.default.object.isRequired,isMulti:y.default.bool,canCreate:y.default.bool.isRequired,ownerID:y.default.number.isRequired,ownerClass:y.default.string.isRequired,ownerRelation:y.default.string.isRequired};var M=(0,o.compose)(l.default,(0,i.connect)(null,(e=>({actions:{toasts:(0,o.bindActionCreators)(c,e)}}))))(C);t.default=M},606:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;s(n(754));var r=function(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=u(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}r.default=e,n&&n.set(e,r);return r}(n(363)),o=s(n(475)),i=n(624),l=s(n(686)),a=s(n(86));function u(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(u=function(e){return e?n:t})(e)}function s(e){return e&&e.__esModule?e:{default:e}}function d(){return d=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},d.apply(this,arguments)}const c=e=>{let{type:t,editing:n,data:i,actions:l,onSubmit:a,...u}=e;if(!t)return!1;(0,r.useEffect)((()=>{n?l.initModal():l.reset()}),[n]);const s=i?{ID:i.FileID,Description:i.Title,TargetBlank:!!i.OpenInNew}:{};return r.default.createElement(o.default,d({isOpen:n,type:"insert-link",title:!1,bodyClassName:"modal__dialog",className:"insert-link__dialog-wrapper--internal",fileAttributes:s,onInsert:e=>{let{ID:n,Description:r,TargetBlank:o}=e;return a({FileID:n,Title:r,OpenInNew:o,typeKey:t.key},"",(()=>{}))}},u))};c.propTypes={type:l.default.isRequired,editing:a.default.bool.isRequired,data:a.default.object.isRequired,actions:a.default.object.isRequired,onClick:a.default.func.isRequired};var f=(0,i.connect)((function(){return{}}),(function(e){return{actions:{initModal:()=>e({type:"INIT_FORM_SCHEMA_STACK",payload:{formSchema:{type:"insert-link",nextType:"admin"}}}),reset:()=>e({type:"RESET"})}}}))(c);t.default=f},117:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=function(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=c(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}r.default=e,n&&n.set(e,r);return r}(n(363)),o=d(n(912)),i=n(852),l=d(n(872)),a=d(n(902)),u=d(n(510)),s=d(n(86));function d(e){return e&&e.__esModule?e:{default:e}}function c(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(c=function(e){return e?n:t})(e)}const f=(e,t)=>{const{schemaUrl:n}=u.default.getSection("SilverStripe\\LinkField\\Controllers\\LinkFieldController").form.linkForm,o=l.default.parse(n),s=a.default.parse(o.query);s.typeKey=e;const{ownerID:d,ownerClass:c,ownerRelation:f}=(0,r.useContext)(i.LinkFieldContext);s.ownerID=d,s.ownerClass=c,s.ownerRelation=f;for(const e of["href","path","pathname"])o[e]=`${o[e]}/${t}`;return l.default.format({...o,search:a.default.stringify(s)})},p=e=>{let{typeTitle:t,typeKey:n,linkID:l=0,isOpen:a,onSuccess:u,onClosed:s}=e;const{actions:d}=(0,r.useContext)(i.LinkFieldContext);if(!n)return!1;return r.default.createElement(o.default,{title:t,isOpen:a,schemaUrl:f(n,l),identifier:"Link.EditingLinkInfo",onSubmit:async(e,t,n)=>{let r=null;try{r=await n()}catch(e){return d.toasts.error(i18n._t("LinkField.FAILED_TO_SAVE_LINK","Failed to save link")),Promise.resolve()}if(!r.id.match(/\/schema\/linkfield\/([0-9]+)/)){const e=r.id.match(/\/linkForm\/([0-9]+)/),t=parseInt(e[1],10);u(t)}return Promise.resolve()},onClosed:s})};p.propTypes={typeTitle:s.default.string.isRequired,typeKey:s.default.string.isRequired,linkID:s.default.number,isOpen:s.default.bool.isRequired,onSuccess:s.default.func.isRequired,onClosed:s.default.func.isRequired};var y=p;t.default=y},809:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=t.Component=void 0;var r=d(n(754)),o=function(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=s(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}r.default=e,n&&n.set(e,r);return r}(n(363)),i=d(n(86)),l=d(n(820)),a=d(n(97)),u=(d(n(686)),d(n(697)));function s(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(s=function(e){return e?n:t})(e)}function d(e){return e&&e.__esModule?e:{default:e}}const c=e=>{let{types:t,onModalSuccess:n,onModalClosed:i,canCreate:s}=e;const[d,c]=(0,o.useState)(""),f=""!==d,p=(0,l.default)("link-picker","form-control"),y=Object.values(t);return s?o.default.createElement("div",{className:p},o.default.createElement(a.default,{types:y,onSelect:e=>{c(e)}}),f&&o.default.createElement(u.default,{types:t,typeKey:d,isOpen:f,onSuccess:e=>{c(""),n(e)},onClosed:()=>{"function"==typeof i&&i(),c("")}})):o.default.createElement("div",{className:p},o.default.createElement("div",{className:"link-picker__cannot-create"},r.default._t("LinkField.CANNOT_CREATE_LINK","Cannot create link")))};t.Component=c,c.propTypes={types:i.default.object.isRequired,onModalSuccess:i.default.func.isRequired,onModalClosed:i.default.func,canCreate:i.default.bool.isRequired};var f=c;t.default=f},97:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=d(n(754)),o=function(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=s(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}r.default=e,n&&n.set(e,r);return r}(n(363)),i=d(n(86)),l=n(127),a=n(852),u=d(n(686));function s(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(s=function(e){return e?n:t})(e)}function d(e){return e&&e.__esModule?e:{default:e}}const c=e=>{let{types:t,onSelect:n}=e;const[i,u]=(0,o.useState)(!1),{loading:s}=(0,o.useContext)(a.LinkFieldContext);return o.default.createElement(l.Dropdown,{disabled:s,isOpen:i,toggle:()=>u((e=>!e)),className:"link-picker__menu"},o.default.createElement(l.DropdownToggle,{className:"link-picker__menu-toggle font-icon-plus-1",caret:!0},r.default._t("LinkField.ADD_LINK","Add Link")),o.default.createElement(l.DropdownMenu,null,t.map((e=>{let{key:t,title:r,icon:i}=e;return o.default.createElement(l.DropdownItem,{key:t,onClick:()=>n(t)},o.default.createElement("span",{className:`link-picker__menu-icon ${i}`}),r)}))))};c.propTypes={types:i.default.arrayOf(u.default).isRequired,onSelect:i.default.func.isRequired};var f=c;t.default=f},734:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=d(n(820)),o=d(n(754)),i=function(e,t){if(!t&&e&&e.__esModule)return e;if(null===e||"object"!=typeof e&&"function"!=typeof e)return{default:e};var n=s(t);if(n&&n.has(e))return n.get(e);var r={},o=Object.defineProperty&&Object.getOwnPropertyDescriptor;for(var i in e)if("default"!==i&&Object.prototype.hasOwnProperty.call(e,i)){var l=o?Object.getOwnPropertyDescriptor(e,i):null;l&&(l.get||l.set)?Object.defineProperty(r,i,l):r[i]=e[i]}r.default=e,n&&n.set(e,r);return r}(n(363)),l=d(n(86)),a=n(127),u=n(852);function s(e){if("function"!=typeof WeakMap)return null;var t=new WeakMap,n=new WeakMap;return(s=function(e){return e?n:t})(e)}function d(e){return e&&e.__esModule?e:{default:e}}const c=e=>t=>{t.nativeEvent.stopImmediatePropagation(),t.preventDefault(),t.nativeEvent.preventDefault(),t.stopPropagation(),e&&e()},f=e=>{let{id:t,title:n,description:l,versionState:s,typeTitle:d,typeIcon:f,onDelete:p,onClick:y,canDelete:v}=e;const{loading:_}=(0,i.useContext)(u.LinkFieldContext),k={"link-picker__link":!0,"form-control":!0};s&&(k[` link-picker__link--${s}`]=!0);const m=(0,r.default)(k),O=["unversioned","unsaved"].includes(s)?o.default._t("LinkField.DELETE","Delete"):o.default._t("LinkField.ARCHIVE","Archive");return i.default.createElement("div",{className:m},i.default.createElement(a.Button,{disabled:_,className:`link-picker__button ${f}`,color:"secondary",onClick:c(y)},i.default.createElement("div",{className:"link-picker__link-detail"},i.default.createElement("div",{className:"link-picker__title"},i.default.createElement("span",{className:"link-picker__title-text"},n),(e=>{let t="",n="";if("draft"===e)t=o.default._t("LinkField.LINK_DRAFT_TITLE","Link has draft changes"),n=o.default._t("LinkField.LINK_DRAFT_LABEL","Draft");else{if("modified"!==e)return null;t=o.default._t("LinkField.LINK_MODIFIED_TITLE","Link has unpublished changes"),n=o.default._t("LinkField.LINK_MODIFIED_LABEL","Modified")}const l=(0,r.default)("badge",`status-${e}`);return i.default.createElement("span",{className:l,title:t},n)})(s)),i.default.createElement("small",{className:"link-picker__type"},d,": ",i.default.createElement("span",{className:"link-picker__url"},l)))),v&&i.default.createElement(a.Button,{disabled:_,className:"link-picker__delete",color:"link",onClick:c((()=>p(t)))},O))};f.propTypes={id:l.default.number.isRequired,title:l.default.string,description:l.default.string,versionState:l.default.string,typeTitle:l.default.string.isRequired,typeIcon:l.default.string.isRequired,onDelete:l.default.func.isRequired,onClick:l.default.func.isRequired,canDelete:l.default.bool.isRequired};var p=f;t.default=p},697:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=l(n(363)),o=n(648),i=l(n(86));function l(e){return e&&e.__esModule?e:{default:e}}const a=e=>{let{types:t,typeKey:n,linkID:i=0,isOpen:l,onSuccess:a,onClosed:u}=e;if(!n)return!1;const s=t.hasOwnProperty(n)?t[n]:{},d=s&&s.hasOwnProperty("handlerName")?s.handlerName:"FormBuilderModal",c=(0,o.loadComponent)(`LinkModal.${d}`);return r.default.createElement(c,{typeTitle:s.title||"",typeKey:n,linkID:i,isOpen:l,onSuccess:a,onClosed:u})};a.propTypes={types:i.default.object.isRequired,typeKey:i.default.string.isRequired,linkID:i.default.number,isOpen:i.default.bool.isRequired,onSuccess:i.default.func.isRequired,onClosed:i.default.func.isRequired};var u=a;t.default=u},41:function(e,t,n){var r=a(n(311)),o=a(n(363)),i=a(n(691)),l=n(648);function a(e){return e&&e.__esModule?e:{default:e}}function u(){return u=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},u.apply(this,arguments)}r.default.entwine("ss",(e=>{e(".js-injector-boot .entwine-linkfield").entwine({Component:null,Root:null,onmatch(){const e=this.closest(".cms-content").attr("id"),t=e?{context:e}:{},n=this.data("schema-component"),r=(0,l.loadComponent)(n,t);this.setComponent(r),this.setRoot(i.default.createRoot(this[0])),this._super(),this.refresh()},refresh(){const e=this.getProps();this.getInputField().val(e.value);const t=this.getComponent();this.getRoot().render(o.default.createElement(t,u({},e,{noHolder:!0})))},handleChange(e){this.getInputField().data("value",e),this.refresh()},getProps(){const e=this.getInputField();return{value:e.data("value"),ownerID:e.data("owner-id"),ownerClass:e.data("owner-class"),ownerRelation:e.data("owner-relation"),onChange:this.handleChange.bind(this),isMulti:this.data("is-multi")??!1,types:this.data("types")??{},canCreate:!!e.data("can-create")}},getInputField(){const t=this.data("field-id");return e(`#${t}`)},onunmatch(){const e=this.getRoot();e&&e.unmount()}})}))},686:function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r,o=(r=n(86))&&r.__esModule?r:{default:r};var i=o.default.shape({key:o.default.string.isRequired,title:o.default.string.isRequired,icon:o.default.string.isRequired});t.default=i},159:function(e){e.exports=Backend},510:function(e){e.exports=Config},42:function(e){e.exports=FieldHolder},912:function(e){e.exports=FormBuilderModal},648:function(e){e.exports=Injector},475:function(e){e.exports=InsertMediaModal},264:function(e){e.exports=Loading},872:function(e){e.exports=NodeUrl},86:function(e){e.exports=PropTypes},363:function(e){e.exports=React},691:function(e){e.exports=ReactDomClient},624:function(e){e.exports=ReactRedux},127:function(e){e.exports=Reactstrap},827:function(e){e.exports=Redux},123:function(e){e.exports=ToastsActions},820:function(e){e.exports=classnames},754:function(e){e.exports=i18n},311:function(e){e.exports=jQuery},902:function(e){e.exports=qs}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,n),i.exports}n(274),n(41)}();