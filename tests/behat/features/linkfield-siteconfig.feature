@retry @job2
Feature: Create Links in LinkField and MultiLinkField on SiteConfig
As a content editor
I want to add links to SiteConfig which has a slightly different EditForm to SiteTree

  Background:
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "SilverStripe\SiteConfig\SiteConfig" class
    And I go to "/dev/build?flush"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/settings"

  Scenario: I can update and save single and multi link fields

    Given I should see the "#Form_EditForm_HasOneLink" element
    And I should see the "#Form_EditForm_HasManyLinks" element

    # Saving empty fields works
    When I press the "Save" button
    And I wait for 2 seconds
    Then I should see a "Saved" success toast

    # Saving populated fields works
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] button" element
    Then I should see "Phone number" in the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element
    And I wait for 5 seconds
    Then I should see "Phone number" in the ".modal-header" element
    Then I fill in "LinkText" with "Phone"
    Then I fill in "Phone" with "12345678"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button" element
    Then I should see "Phone number" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I wait for 5 seconds
    Then I should see "Phone number" in the ".modal-header" element
    Then I fill in "LinkText" with "Phone"
    Then I fill in "Phone" with "87654321"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    When I press the "Save" button
    And I wait for 2 seconds
    Then I should see a "Saved" success toast
    And I should see "12345678"
    And I should see "87654321"
