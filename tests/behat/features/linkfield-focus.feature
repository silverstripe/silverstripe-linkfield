@retry @job2
Feature: Browser focus in linkfield
As a content editor
I want to focus to be in the correct place

  Background:
    Given I add an extension "SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension" to the "Page" class
    And I go to "/dev/build?flush"
    And a "page" "Link Page"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section"
    And I am logged in as a member of "EDITOR" group
    And I go to "/admin/pages"
    And I should see "Link Page"
    And I click on "Link Page" in the tree

  Scenario: Single-linkfield focus

    # Clicking on the linkfield sets focus
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__menu-toggle" element
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__menu-toggle"

    # Create a new link with submit sets focus on the newly created link
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element
    # Focus should now be on the first input of the modal form
    Then the active element should be "#Form_LinkForm_0_Phone"
    When I type "123456789" in the field
    And I click on the ".form-builder-modal .btn-primary" element
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__button"

    # Editing existing link without submit sets focus on the existing link
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__button" element
    And I click on the ".form-builder-modal .btn-close" element
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__button"

    # Editing existing link with submit sets focus on the existing link
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__button" element
    And I type "987654321" in the field
    And I click on the ".form-builder-modal .btn-primary" element
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__button"

    # Deleting the link sets focus on the link picker
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__delete" element, confirming the dialog
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__menu-toggle"

    # Create a new link without submit sets focus on the link picker
    When I click on the "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__menu-toggle" element
    And I click on the "[data-field-id='Form_EditForm_HasOneLink'] .dropdown-item:nth-of-type(3)" element
    And I click on the ".form-builder-modal .btn-close" element
    Then the active element should be "[data-field-id='Form_EditForm_HasOneLink'] .link-picker__menu-toggle"

  Scenario: Multi-linkfield focus

    # Clicking on the linkfield sets focus
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle"

    # Create a new link with submit sets focus on the link-picker
    And I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I type "111111111" in the field
    And I click on the ".form-builder-modal .btn-primary" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle"

    # Editing existing link without submit sets focus on the existing link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__button" element
    And I click on the ".form-builder-modal .btn-close" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__button"

    # Editing existing link with submit sets focus on the existing link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__button" element
    And I type "111111111b" in the field
    And I click on the ".form-builder-modal .btn-primary" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__button"

    # Creating a new link without submit sets focus on the link-picker
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle" element
    And I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I click on the ".form-builder-modal .btn-close" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle"

    # Creating a second link with submit sets focus on the link-picker
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle" element
    And I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I type "222222222" in the field
    And I click on the ".form-builder-modal .btn-primary" element
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle"

    # Create a third link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle" element
    And I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .dropdown-item:nth-of-type(5)" element
    And I type "333333333" in the field
    And I click on the ".form-builder-modal .btn-primary" element

    # Archiving the non-last link focuses on what was the next link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(1) .link-picker__delete" element, confirming the dialog
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(1) .link-picker__button"

    # Archiving the last link focuses on what was the previous link
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(2) .link-picker__delete" element, confirming the dialog
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(1) .link-picker__button"

    # Archiving the only link focuses on the link-picker
    When I click on the "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__link:nth-of-type(1) .link-picker__delete" element, confirming the dialog
    Then the active element should be "[data-field-id='Form_EditForm_HasManyLinks'] .link-picker__menu-toggle"
