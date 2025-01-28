@retry @job1
Feature: Accessibility Tests
  As a content editor
  I want to create and interact with LinkField using the keyboard

  Background:
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "Page" class
    And I go to "/dev/build?flush"
    And there are the following Page records
    """
    page-1:
      Title: "Page 1"
      URLSegment: 'page-1'
    """
    And there are the following SilverStripe\LinkField\Models\SiteTreeLink records
    """
    page-link-1:
      OwnerID: 1
      LinkText: 'Page Link 1'
      QueryString: 'param1=value1&param2=option2'
      Anchor: 'my-anchor'
      Page: =>Page.page-1
    """
    And there are the following SilverStripe\LinkField\Models\EmailLink records
    """
    email-link:
      LinkText: 'Email Link'
      Email: 'maxime@silverstripe.com'
    """
    And there are the following SilverStripe\LinkField\Models\ExternalLink records
    """
    external-link:
      LinkText: 'External Link'
      ExternalUrl: 'https://google.com'
    """
    And there are the following Page records
    """
    link-page-1:
      Title: 'Link Page'
      URLSegment: 'link-page-1'
      HasManyLinks:
        - =>SilverStripe\LinkField\Models\SiteTreeLink.page-link-1
        - =>SilverStripe\LinkField\Models\EmailLink.email-link
        - =>SilverStripe\LinkField\Models\ExternalLink.external-link
    """
    And I go to "/dev/build?flush"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"
    And I should see "Link Page"
    And I click on "Link Page" in the tree

  Scenario: I can create and edit a LinkField using the keyboard

    Given I should see the "#Form_EditForm_HasManyLinks" element
    And I should see the "#Form_EditForm_HasOneLink" element

    # Create SiteTreeLink in LinkField using keyboard

    When I focus on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
    And I press the "Enter" key globally
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(1)"
    When I press the "Enter" key globally
    And I wait for 2 seconds
    Then I should see "Page on this site" in the ".modal-header" element
    And I wait for 2 seconds

    # Test accessibility of the modal form

    Then I type "About Us" in the active element "#Form_LinkForm_0_PageID"
    And I press the "Enter" key globally
    And I press the "Tab" key globally

    Then I type "newquery=1" in the active element "[name='QueryString']"
    And I press the "Tab" key globally

    Then I type "new anchor" in the active element ".anchorselectorfield__input"
    And I press the "Enter" key globally
    And I press the "Tab" key globally
    When I press the "Enter" key globally
    Then I should see "Auto generated from Page title if left blank"
    And I press the "Enter" key globally
    And I press the "Tab" key globally

    Then I type "New Page Link" in the active element "[name='LinkText']"
    And I press the "Tab" key globally
    Then the active element should be "[name='OpenInNew']"
    And I press the "Space" key globally
    And I press the "Tab" key globally
    Then I press the "Enter" key globally
    And I wait for 2 seconds

    Then I should see "New Page Link" in the "[data-field-id='Form_EditForm_HasOneLink']" element
    And I should see "about-us" in the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__url" element

    # Test accessibility of the LinkField menu

    Then I press the "Tab" key globally
    And I press the "Tab" key globally
    And I press the "Enter" key globally
    And I should see "Page on this site" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-menu.show" element
    And I press the "Down" key globally
    And I press the "Down" key globally
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)"
    And I press the "Enter" key globally
    Then I should see "Link to external URL" in the ".modal-header" element
    And I press the "Tab" key globally
    Then I should see "External URL is required" in the ".modal-content" element
    And I press the "Tab" key globally
    And I press the "Tab" key globally
    And I press the "Tab" key globally
    And I press the "Tab" key globally
    And the active element should be ".btn-close"
    And I press the "Enter" key globally
    And I wait for 2 seconds

    # Test accessibility of the LinkField keyboard sorting

    Then I should see "Page Link 1" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "External Link" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
    When I press the "Tab" key globally
    When I press the "Tab" key globally
    When I press the "Tab" key globally
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__drag-handle"
    Then I press the "Enter" key globally
    And I press the "Down" key globally
    And I press the "Down" key globally
    And I press the "Enter" key globally
    Then I should see "Email Link" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "External Link" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(2)" element
    And I should see "Page Link 1" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element

    # Test user can delete the link

    Then I press the "Shift-Tab" key globally
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(3) .link-picker__delete"
    And I press the "Enter" key and confirm the dialog
    And I wait for 3 seconds
    Then I should not see "Page Link 1" in the "[data-field-id='Form_EditForm_HasManyLinks']" element
    And I press the "Enter" key globally
