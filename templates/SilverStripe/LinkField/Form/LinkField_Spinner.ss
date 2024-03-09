<%-- This template is here to bootstrap a LinkField React form field --%>
<%-- It includes a pre-rendered spinner to provide a nicer UI while waiting for React to boot --%>
<%-- Once React is done pre-rendering, it will discard the pre-rendered markup --%>
<div class="link-field__loading">
    <div class="cms-content-loading-overlay ui-widget-overlay-light"></div>
    <% include SilverStripe/Admin/Includes/CMSLoadingSpinner  %>
</div>


