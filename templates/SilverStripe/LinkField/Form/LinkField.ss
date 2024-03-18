<%-- This template is here to bootstrap a LinkField React form field --%>
<%-- It includes some pre-rendered content to provide a nicer UI while waiting for React to boot --%>
<%-- Once React is done pre-rendering, it will discard the pre-rendered markup --%>
<input $AttributesHTML />
<div data-field-id="$ID" data-schema-component="$SchemaComponent" class="entwine-linkfield" data-types="$TypesProp">
  <div class="link-field__container">
    <% include SilverStripe/LinkField/Form/LinkField_Spinner  %>
    <div>
        <div class="link-picker__link link-picker__link--is-first link-picker__link--is-last form-control link-picker__link--disabled link-picker__link--published" role="button" aria-disabled="false" aria-roledescription="sortable" aria-describedby="" id="link-picker__link-42">
            <button type="button" disabled="" class="link-picker__button font-icon-link btn btn-secondary disabled"></button>
        </div>
    </div>
  </div>
</div>

