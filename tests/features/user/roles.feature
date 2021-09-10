@api
Feature: User role management
  As a moderator I must be able to assign roles to users.

  Background:
    Given users:
      | Username   | Roles     | E-mail                 |
      | Rick Rolls | Moderator | rick.roles@example.com |
      | Nibby Noob |           | nicky.noob@example.com |
      | Ursus      |           |                        |
    And all e-mails have been sent

  Scenario: Verify options available to the moderator.
    Given I am logged in as "Rick Rolls"
    And I am on the homepage
    And I click "People"

    Then the "Action" field should contain the "Add the Moderator role to the selected user(s), Remove the Moderator role from the selected user(s), Add the Licence manager role to the selected user(s), Remove the Licence manager role from the selected user(s)" options
    And the available options in the "Action" select should not include the "Add the Administrator role to the selected user(s), Remove the Administrator role from the selected user(s)" options

  Scenario: A moderator can assign the moderator role.
    Given I am logged in as "Rick Rolls"
    And I am on the homepage
    And I click "People"

    When I fill in "Name or email contains" with "Nibby Noob"
    And I press the "Filter" button
    # Select user and assign role
    And I check "Nibby Noob"
    And I select "Add the Moderator role to the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Add the Moderator role to the selected user(s) was applied to 1 item."
    And I should see the success message "The user has been notified that their account has been updated."
    And the following email should have been sent:
      | recipient | Nibby Noob                                                                                                |
      | subject   | The Joinup Support Team updated your account for you at Joinup                                            |
      | body      | A moderator has edited your user profile on Joinup. Please check your profile to verify the changes done. |

    Given I am on the homepage
    When all e-mails have been sent
    And I click "People"

    When I click "Edit" in the "Ursus" row
    And I fill in "First name" with "Ur"
    And I fill in "Family name" with "Sus"
    And I press "Save"
    Then I should see the success message "The changes have been saved."
    But I should not see the success message "The user has been notified that their account has been updated."
    And 0 e-mails should have been sent

  Scenario: A moderator can assign and remove the Licence manager role.
    Given the following licence:
      | title       | Open licence              |
      | description | Licence agreement details |
      | type        | Public domain             |

    And I am logged in as "Rick Rolls"
    And I am on the homepage
    And I click "People"

    When I fill in "Name or email contains" with "Nibby Noob"
    And I press the "Filter" button
    # Select user and assign role
    And I check "Nibby Noob"
    And I select "Add the Licence manager role to the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Add the Licence manager role to the selected user(s) was applied to 1 item."
    And I should see the success message "The user has been notified that their account has been updated."
    And the following email should have been sent:
      | recipient | Nibby Noob                                                                                                |
      | subject   | The Joinup Support Team updated your account for you at Joinup                                            |
      | body      | A moderator has edited your user profile on Joinup. Please check your profile to verify the changes done. |

    When I am logged in as "Nibby Noob"
    And I go to the "Open licence" licence
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Closed licence"
    And I press "Save"
    Then I should see the heading "Closed licence"

  Scenario: A moderator can assign and remove the 'RDF graph manager' role.

    Given I am logged in as "Rick Rolls"
    When I click "People"
    Then I should see the link "Nibby Noob"
    And  I should not see the text "RDF graph manager" in the "Nibby Noob" row

    When I check "Nibby Noob"
    And I select "Add the RDF graph manager role to the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Add the RDF graph manager role to the selected user(s) was applied to 1 item."
    And I should see the text "RDF graph manager" in the "Nibby Noob" row

    When I check "Nibby Noob"
    And I select "Remove the RDF graph manager role from the selected user(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the success message "Remove the RDF graph manager role from the selected user(s) was applied to 1 item."
    And I should not see the text "RDF graph manager" in the "Nibby Noob" row
