@api @group-g
Feature: User management
  As a moderator I must be able to search users.

  Scenario: Moderator can search for users
    # Trusty user created at 05/01/2014
    # Newkid user created at 03/05/2016
    # Old guy user was created at 06/05/2012
    Given users:
      | Username           | Roles         | Created    | Status  |
      | Jolly user manager | Moderator     |            | active  |
      | Trusty user        |               | 1388880000 | blocked |
      | Newkid             |               | 1457136000 | active  |
      | Old guy            | Administrator | 1336262400 | active  |
    Given I am logged in as "Jolly user manager"
    When I am on the homepage
    Then I click "People"
    Then I should be on "admin/people"
    Then I should see the text "Trusty user"
    Then I should see the text "Newkid"

    # Filter on date
    Then I fill in "Account created between" with "1-1-2014"
    And I fill in "And" with "10-1-2014"
    And I press the "Filter" button
    Then I should see the text "Trusty user"
    Then I should not see the text "Newkid"
    Then I press the "Reset" button

    # Filter on blocked
    Then I select "Active" from "Status"
    And I press the "Filter" button
    Then I should see the text "Newkid"
    Then I should not see the text "Trusty user"
    Then I press the "Reset" button

    # Filter by role
    Then I select "Administrator" from "Role"
    And I press the "Filter" button
    Then I should see the text "Old guy"
    But I should not see the text "Trusty user"
    And I should not see the text "Newkid"
    Then I press the "Reset" button
