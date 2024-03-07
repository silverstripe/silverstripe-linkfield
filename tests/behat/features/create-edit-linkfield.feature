@retry
Feature: Create Links in LinkField and MultiLinkField
As a content editor
I want to add links to pages, files, external URLs, email addresses and phone numbers

  Background:
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "Page" class
    And I go to "/dev/build?flush"
    And a "page" "Link Page"
    And a "image" "folder1/file1.jpg"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"
    And I should see "Link Page"
    And I click on "Link Page" in the tree

  Scenario: Link type dropdown has correct values for single linkfield (limited list)
    Given I should see the "#Form_EditForm_HasManyLinks" element
    And I should see the "#Form_EditForm_HasOneLink" element

    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
    Then I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-menu.show" element

    And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(1) .font-icon-page" element
    And I should see "Page on this site" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(1)" element

    And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2) .font-icon-p-mail" element
    And I should see "Link to email address" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element

    And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3) .font-icon-mobile" element
    And I should see "Phone number" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element

  Scenario: Link type dropdown has correct values for multi linkfield (full list)
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-menu.show" element

    And I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(1) .font-icon-page" element
    And I should see "Page on this site" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(1)" element

    And I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2) .font-icon-image" element
    And I should see "Link to a file" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2)" element

    And I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3) .font-icon-external-link" element
    And I should see "Link to external URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element

    And I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(4) .font-icon-p-mail" element
    And I should see "Link to email address" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(4)" element

    And I should see the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5) .font-icon-mobile" element
    And I should see "Phone number" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element

  Scenario: Create and manipulate links
    # Test that user can create email link in single LinkField
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
    Then I should see "Link to email address" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element
    And I wait for 5 seconds
    Then I should see "Link to email address" in the ".modal-header" element
    Then I fill in "LinkText" with "Email link"
    And I fill in "Email" with "email@example.com"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    # Create SiteTreeLink in MultiLinkField
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Page on this site" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-menu.show" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(1)" element
    Then I should see "Page on this site" in the ".modal-header" element
    And I wait for 2 seconds
    And I press the "Create link" button
    Then I should see "Page is required" in the ".modal-content" element
    Then I fill in "LinkText" with "About Us"
    And I select "About Us" in the "#Form_LinkForm_0_PageID_Holder" tree dropdown
    And I fill in "QueryString" with "option=value"
    And I check "Open in new window"
    And I press the "Create link" button
    And I wait for 2 seconds

    # Create ExternalLink in MultiLinkField
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Link to external URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    And I wait for 5 seconds
    Then I should see "Link to external URL" in the ".modal-header" element
    Then I fill in "LinkText" with "External URL"
    And I fill in "ExternalUrl" with "w1234@$%"
    And I press the "Create link" button
    Then I should see "Please enter a valid URL" in the ".modal-content" element
    Then I fill in "ExternalUrl" with "https://www.silverstripe.org"
    And I check "Open in new window"
    And I press the "Create link" button
    And I wait for 2 seconds

    # Create PhoneLink in MultiLinkField
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Phone number" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I wait for 5 seconds
    Then I should see "Phone number" in the ".modal-header" element
    Then I fill in "LinkText" with "Phone"
    Then I fill in "Phone" with "12345678"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    # Create FileLink in MultiLinkField

    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Link to a file" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2)" element
    And I wait for 5 seconds
    Then I should see "Link to a file" in the ".modal-header" element
    And I should see "Open in new window" in the ".modal-content" element
    When I fill in "LinkText" with "File link"
    And I click "Choose existing" in the ".uploadfield" element
    And I press the "Back" HTML field button
    # open "folder1"
    And I click on the ".gallery__folders > :nth-child(1) label" element
    # select "file1"
    And I click on the ".gallery__files .gallery-item[role='button']" element
    And I press the "Insert" button
    And I press the "Create link" button
    And I wait for 2 seconds

    # Test that all links are created

    # Link in single link field
    Then I should see "Email link" in the "[data-field-id='Form_EditForm_HasOneLink']" element
    And I should see "email@example.com" in the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__link" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasOneLink']" element

    # First link in multi link field
    And I should see "About Us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "about-us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element

    # Second link in multi link field
    And I should see "External URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(2)" element
    And I should see "https://www.silverstripe.org" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(2)" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(2)" element

    # Third link in multi link field
    And I should see "Phone number" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(3)" element
    And I should see "12345678" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(3)" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(3)" element

    # Fourth link in multi link field
    And I should see "Link to a file" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(4)" element
    And I should see "folder1/file1.jpg" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(4)" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(4)" element

    # Test that user can publish the page with links

    When I press the "Publish" button
    And I wait for 2 seconds
    And I should not see "Draft" in the "[data-field-id='Form_EditForm_HasOneLink']" element
    And I should not see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks']" element

    # Test that user can edit links

    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first button" element
    Then I should see "Page on this site" in the ".modal-header" element
    Then I fill in "LinkText" with "All about us"
    And I press the "Update link" button
    And I wait for 2 seconds
    And I should see "All about us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "Modified" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element

    # Test that user can reorder links (move first item to last position)

    And I drag the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element to the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
    And I wait for 3 seconds
    And I should see "All about us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
    And I should see "External URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element

    # Test that user can delete the link

    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__delete" element, confirming the dialog
    And I wait for 3 seconds
    Then I should not see "Email link" in the "[data-field-id='Form_EditForm_HasOneLink']" element
    Then I press the "Publish" button

  Scenario: Create file link with nested redux form
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Link to a file" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(2)" element
    And I wait for 5 seconds
    Then I should see "Link to a file" in the ".modal-header" element
    And I should see "Open in new window" in the ".modal-content" element
    When I fill in "LinkText" with "File link"
    And I click "Choose existing" in the ".uploadfield" element
    And I press the "Back" HTML field button
    # open "folder1"
    And I click on the ".gallery__folders > :nth-child(1) label" element
    # select "file1"
    And I click on the ".gallery__files .gallery-item[role='button']" element
    # Resize screen so we have "insert file" instead of just "insert" - they're actually buttons for completely different forms.
    And I set the screen width to 700px
    And I press the "Insert file" button
    And I press the "Create link" button
    And I reset the screen size
    And I wait for 2 seconds

    And I should see "Link to a file" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "folder1/file1.jpg" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
    And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
