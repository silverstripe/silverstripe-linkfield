@retry @job2
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
    # Publish the page straight away
    And I click on the "#Form_EditForm_action_publish" element
    And I wait for 3 seconds

  Scenario: Change tracker single link
    # save/publish buttons will get .btn.primary class when clicking them will make a change to the database
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element
    # Create email link in single LinkField
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
    Then I should see "Link to email address" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(2)" element
    And I wait for 3 seconds
    Then I should see "Link to email address" in the ".modal-header" element
    Then I fill in "LinkText" with "Email link"
    And I fill in "Email" with "email@example.com"
    And I press the "Create link" button
    And I wait for 2 seconds
    # Because of the use of AJAX, the save button should not be highlighted, though the publish button should
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should see a "#Form_EditForm_action_publish.btn-primary" element
    # Publish the page
    When I click on the "#Form_EditForm_action_publish" element
    And I wait for 3 seconds
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element
    # Check that clicking on an unrelated input field doesn't activate the change tracker
    When I click on the "#Form_EditForm_Title" element
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element

  Scenario: Change tracker multi link
    # save/publish buttons will get .btn.primary class when clicking them will make a change to the database
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element
    # Create PhoneLink in MultiLinkField
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Phone number" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I wait for 3 seconds
    Then I should see "Phone number" in the ".modal-header" element
    Then I fill in "LinkText" with "Phone"
    Then I fill in "Phone" with "12345678"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds
    # Because of the use of AJAX, the save button should not be highlighted, though the publish button should
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should see a "#Form_EditForm_action_publish.btn-primary" element
    # Publish the page
    When I click on the "#Form_EditForm_action_publish" element
    And I wait for 3 seconds
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element
    # Check that clicking on an unrelated input field doesn't activate the change tracker
    When I click on the "#Form_EditForm_Title" element
    Then I should not see a "#Form_EditForm_action_save.btn-primary" element
    And I should not see a "#Form_EditForm_action_publish.btn-primary" element

