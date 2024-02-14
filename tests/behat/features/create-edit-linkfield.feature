@retry
Feature: Create Links in LinkField and MultiLinkField
As a content editor
I want to add links to pages, files, external URLs, email addresses and phone numbers

  Background:
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "Page" class
      And I go to "/dev/build?flush"
      And a "page" "Link Page"
      And the "group" "EDITOR" has permissions "Access to 'Pages' section"
      And I am logged in as a member of "EDITOR" group
      And I go to "/admin/pages"
      And I should see "Link Page"
      And I click on "Link Page" in the tree

  Scenario: I click on the link fields and see the list of allowed link types with icons for LinkFields
    Given I should see the "#Form_EditForm_HasManyLinks" element
      And I should see the "#Form_EditForm_HasOneLink" element

      # Test limited list of link types are present in correct order in the dropdown

      When I click on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
      Then I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-menu.show" element

      And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(1) .font-icon-page" element
      And I should see "Page on this site" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(1)" element

      And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2) .font-icon-p-mail" element
      And I should see "Link to email address" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element

      And I should see the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3) .font-icon-mobile" element
      And I should see "Phone number" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element

      # Test full list of link types are present in correct order in the dropdown

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

      # Test that user can create email link in LinkField

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

      # Test that all links are created

      # Link ID 1
      Then I should see "Email link" in the "[data-field-id='Form_EditForm_HasOneLink']" element
      And I should see "email@example.com" in the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__link" element
      And I should see "Draft" in the "[data-field-id='Form_EditForm_HasOneLink']" element

      # Link ID 2
      And I should see "About Us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
      And I should see "about-us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element
      And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element

      # Link ID 3
      And I should see "External URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
      And I should see "https://www.silverstripe.org" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
      And I should see "Draft" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element

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

      # Test that user can reorder links

      And I drag the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element to the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
      And I wait for 3 seconds
      And I should see "All about us" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-last" element
      And I should see "External URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link--is-first" element

      # Test that user can delete the link

      When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__delete" element, confirming the dialog
      And I wait for 3 seconds
      Then I should not see "Email link" in the "[data-field-id='Form_EditForm_HasOneLink']" element
      Then I press the "Publish" button