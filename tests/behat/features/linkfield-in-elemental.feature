Feature: Create Links in LinkField and MultiLinkField as part of Elemental Block
As a content editor
I want to be able to work with LinkField and MultiLinkField in Elemental Block

  Background:
    Given I add an extension "DNADesign\Elemental\Extensions\ElementalPageExtension" to the "Page" class
      And I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\ElementContentExtension" to the "DNADesign\Elemental\Models\BaseElement" class
      And I go to "/dev/build?flush"
      And a "page" "Link Blocks Page"
      And the "group" "EDITOR" has permissions "Access to 'Pages' section"
      And I am logged in as a member of "EDITOR" group
      And I go to "/admin/pages"
      And I should see "Link Blocks Page"
      And I click on "Link Blocks Page" in the tree

  Scenario: I can create link blocks page
    Given I press the "Add block" button
    # There are few buttons on the page with 'Content' text
    Then I click on the ".font-icon-block-content" element
    Then I should see "Untitled Content block" in the ".element-editor__element" element
    And I click on the ".element-editor__element" element

    # Test that user can create link in LinkField

    When I click on the "#Form_ElementForm_1_PageElements_1_OneLink_Holder button" element
    Then I should see the "#Form_ElementForm_1_PageElements_1_OneLink_Holder .dropdown-menu.show" element
    And I should see "Link to email address" in the "#Form_ElementForm_1_PageElements_1_OneLink_Holder .dropdown-item:nth-of-type(2)" element
    When I click on the "#Form_ElementForm_1_PageElements_1_OneLink_Holder .dropdown-item:nth-of-type(2)" element
    And I wait for 5 seconds
    Then I should see "Link to email address" in the ".modal-header" element
    Then I fill in "LinkText" with "Email link"
    And I fill in "Email" with "email@example.com"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    # Test that user can create link in MultiLinkField
    # Create SiteTreeLink in MultiLinkField

    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder button" element
    Then I should see the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-menu.show" element
    And I should see "Page on this site" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(1)" element
    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(1)" element
    Then I should see "Page on this site" in the ".modal-header" element
    And I wait for 2 seconds
    Then I fill in "LinkText" with "About Us"
    And I select "About Us" in the "#Form_LinkForm_0_PageID_Holder" tree dropdown
    And I fill in "QueryString" with "option=value"
    And I check "Open in new window"
    And I press the "Create link" button
    And I wait for 2 seconds

    # Create ExternalLink in MultiLinkField

    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder button" element
    Then I should see the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-menu.show" element
    And I should see "Phone number" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(5)" element
    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .dropdown-item:nth-of-type(5)" element
    And I wait for 5 seconds
    Then I should see "Phone number" in the ".modal-header" element
    Then I fill in "LinkText" with "Phone"
    Then I fill in "Phone" with "12345678"
    And I should not see "Open in new window" in the ".modal-content" element
    And I press the "Create link" button
    And I wait for 2 seconds

    # Test that all links are created

    # Link ID 1
    Then I should see "Email link" in the "#Form_ElementForm_1_PageElements_1_OneLink_Holder" element
    And I should see "email@example.com" in the "#Form_ElementForm_1_PageElements_1_OneLink_Holder .link-picker__link" element
    And I should see "Draft" in the "#Form_ElementForm_1_PageElements_1_OneLink_Holder" element

    # Link ID 2
    And I should see "About Us" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element
    And I should see "about-us" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element
    And I should see "Draft" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element

    # Link ID 3
    And I should see "Phone" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-last" element
    And I should see "12345678" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-last" element
    And I should see "Draft" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-last" element

    # Test that user can publish the page with links

    When I press the "Publish" button
    And I wait for 2 seconds
    Then I click on the ".element-editor__element" element
    And I should not see "Draft" in the "#Form_ElementForm_1_PageElements_1_OneLink_Holder" element
    And I should not see "Draft" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder" element

    # Test that user can edit links

    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first button" element
    Then I should see "Page on this site" in the ".modal-header" element
    Then I fill in "LinkText" with "All about us"
    And I press the "Update link" button
    And I wait for 2 seconds
    And I should see "All about us" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element
    And I should see "Modified" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element

    # Test that user can delete the link

    When I click on the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__delete" element, confirming the dialog
    And I wait for 3 seconds
    Then I should not see "All about us" in the "#Form_ElementForm_1_PageElements_1_ManyLinks_Holder .link-picker__link--is-first" element
    Then I press the "Publish" button
