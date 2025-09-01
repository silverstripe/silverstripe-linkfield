<%-- This template is here to bootstrap a MultiLinkField React form field --%>
<%-- It includes some pre-rendered content to provide a nicer UI while waiting for React to boot --%>
<%-- Once React is done pre-rendering, it will discard the pre-rendered markup --%>
<input $AttributesHTML />
<div data-is-multi="true" data-field-id="$ID" data-schema-component="$SchemaComponent" class="entwine-linkfield" data-types="$TypesProp">

  <div class="link-field__container">
    <% include SilverStripe/LinkField/Form/LinkField_Spinner  %>
    <div class="link-picker form-control">
      <div class="link-picker__menu dropdown">
        <button
          type="button"
          aria-haspopup="true"
          aria-expanded="false"
          class="link-picker__menu-toggle dropdown-toggle btn btn-secondary"
          aria-label="<%t SilverStripe\LinkField\Models\MultiLinkField.AddLink "Add link" %>">
            <span class="font-icon-plus-1" aria-hidden="true"></span>
            <%t SilverStripe\LinkField\Models\MultiLinkField.AddLink "Add link" %>
        </button>
      </div>
    </div>
    <div class="link-picker-links">
      <% loop $LinkIDs %>
      <div class="link-picker__link form-control link-picker__link--published <% if $IsFirst %>link-picker__link--is-first<% end_if %> <% if $IsLast %>link-picker__link--is-last<% end_if %>">
        <button
          type="button"
          disabled=""
          class="link-picker__button btn btn-secondary disabled"
          aria-label="<%t SilverStripe\LinkField\Models\MultiLinkField.EditLink "Edit link" %>"
        >
          <span class="font-icon-link" aria-hidden="true"></span>
        </button>
      </div>
      <% end_loop %>
    </div>
  </div>

</div>

