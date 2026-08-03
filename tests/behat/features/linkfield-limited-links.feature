@retry @job1
Feature: Validate limits applied to MultiLinkField are respected
  As a content editor
  I want my business logic of a limited number of links per field to be reflected in the CMS

  Background:
    Given the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group

  Scenario: Limited links in a react form
    Given I add an extension "DNADesign\Elemental\Extensions\ElementalPageExtension" to the "Page" class without dev-build
    And I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\ElementContentExtension" to the "DNADesign\Elemental\Models\BaseElement" class without dev-build
    And I add an extension "SilverStripe\LinkField\Tests\Behat\Context\Extension\LinkLimitExtension" to the "DNADesign\Elemental\Models\BaseElement" class
    And a "page" "Link Blocks Page"
    And I go to "/admin/pages"
    And I click on "Link Blocks Page" in the tree
    # create elemental block
    And I press the "Add new block" button
    And I click on the ".font-icon-block-content" element
    # The block is added via a GraphQL request, which the ajax step handler doesn't wait for
    And I wait until I see the text "Untitled Content block"
    Then I should see "Untitled Content block" in the ".element-editor__element" element
    # open elemental block
    When I click on the ".element-editor__element" element
    Then I should not see "You have reached the maximum number of links"
    # create first link
    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder button.link-picker__menu-toggle" element
    And I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(4)" element
    And I wait for 5 seconds until I see the ".modal-header" element
    And I fill in "Email" with "email@example.com"
    And I press the "Create link" button
    Then I should not see "You have reached the maximum number of links"
    # create second link - limit is 2 total links
    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder button.link-picker__menu-toggle" element
    And I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(4)" element
    And I wait for 5 seconds until I see the ".modal-header" element
    And I fill in "Email" with "email2@example.com"
    And I press the "Create link" button
    Then I should see "You have reached the maximum number of links"
    And I should not see the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder button.link-picker__menu-toggle" element
    # Save so the driver can reset without having to deal with the popup alert.
    Then I press the "Save" button

  Scenario: Limited links in an entwine form
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "Page" class without dev-build
    And I add an extension "SilverStripe\LinkField\Tests\Behat\Context\Extension\LinkLimitExtension" to the "Page" class
    And a "page" "Link Page"
    And I go to "/admin/pages"
    And I click on "Link Page" in the tree
    # create first link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button.link-picker__menu-toggle" element
    Then I should see "Link to external URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    And I wait for 5 seconds until I see the ".modal-header" element
    Then I fill in "LinkText" with "External URL"
    And I fill in "ExternalUrl" with "https://www.example.com"
    And I press the "Create link" button
    Then I should not see "You have reached the maximum number of links"
    # create second link - limit is 2 total links
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] button.link-picker__menu-toggle" element
    Then I should see "Link to external URL" in the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(3)" element
    And I wait for 5 seconds until I see the ".modal-header" element
    Then I fill in "LinkText" with "External URL"
    And I fill in "ExternalUrl" with "https://www.example.org"
    And I press the "Create link" button
    Then I should see "You have reached the maximum number of links"
    And I should not see the "[data-field-id='Form_EditForm_HasManyLinks'] button.link-picker__menu-toggle" element
    # Save so the driver can reset without having to deal with the popup alert.
    Then I press the "Save" button
