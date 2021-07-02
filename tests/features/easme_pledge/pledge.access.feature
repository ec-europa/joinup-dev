@api @group-a
Feature:
  As a moderator of the website
  In order to ensure the security of the website
  I need to ensure that access control is set up properly.

  Scenario Outline: No user can create a pledge directly.
    Given I am <type of login>
    When I go to "node/add/pledge"
    Then I should see the heading "<error message>"

    Examples:
      | type of login                                   | error message       |
      | an anonymous user                               | Sign in to continue |
      | logged in as a user with the authenticated role | Access denied       |
      | logged in as a user with the moderator role     | Access denied       |

  Background:
    Given the following collection:
      | title | Institute challenge |
      | state | validated           |
    And the following solution:
      | title      | Possible solution to institute challenge |
      | state      | validated                                |
      | collection | Institute challenge                      |
    And user:
      | Username    | Pledge Owner             |
      | First name  | Pledge                   |
      | Family name | Owner                    |
      | E-mail      | pledge_owner@example.com |

  Scenario: Anonymous users can see the pledge button but need to login to submit a pledge.
    Given I am an anonymous user
    And I go to the "Possible solution to institute challenge" solution
    Then I should see the "Pledge" button in the "Header" region
    # The anonymous users need to login first.
    Given I press "Pledge" in the "Header" region
    Then I should see the text "Sign in to continue"

  Scenario Outline: Proper roles should have proper submit buttons in the pledge creation form.
    Given pledge content:
      | title       | description           | solution                                 | author       |
      | Some pledge | We would like to help | Possible solution to institute challenge | Pledge Owner |

    When I am logged in as a user with the "<role>" role
    And I go to the "Possible solution to institute challenge" solution
    Then I should see the "Pledge" button in the "Header" region

    # Authenticated users can directly create a pledge.
    When I press "Pledge" in the "Header" region
    Then I should see the heading "Create a pledge"
    And the following fields should be present "Title, Type of contribution, Description, Contact information, Pledge owner"

    # The authenticated users can only save as draft and submit for approval.
    And the following buttons should be present "<buttons present>"
    But the following buttons should not be present "<buttons not present>"

    Examples:
      | role          | buttons present                                           | buttons not present                     |
      | authenticated | Save as draft, Submit for approval                        | Update, Set as under approval, Validate |
      | moderator     | Save as draft, Submit for approval, Set as under approval | Update, Validate                        |

  Scenario Outline: Only specific roles can see pledges in specific states.
    Given pledge content:
      | title       | description           | solution                                 | author       | state   |
      | Some pledge | We would like to help | Possible solution to institute challenge | Pledge Owner | <state> |

    When I am logged in as "Pledge Owner"
    And I go to the "Some pledge" pledge
    Then I should see the heading "Some pledge"

    When I am an anonymous user
    And I go to the "Some pledge" pledge
    Then I should see the heading "<anonymous heading>"
    And I should not see the link "Edit"

    When I am logged in as a user with the authenticated role
    And I go to the "Some pledge" pledge
    Then I should see the heading "<authenticated heading>"
    And I should not see the link "Edit"

    When I am logged in as a moderator
    And I go to the "Some pledge" pledge
    Then I should see the heading "Some pledge"
    And I should see the link "Edit" in the "Entity actions" region

    Examples:
      | state            | anonymous heading   | authenticated heading |
      | draft            | Sign in to continue | Access denied         |
      | proposed         | Sign in to continue | Access denied         |
      | under_validation | Sign in to continue | Access denied         |
      | validated        | Some pledge         | Some pledge           |

  Scenario: Owners can perform limited actions on a pledge and only when in draft.
    Given pledge content:
      | title                        | description           | solution                                 | author       | state            |
      | Some draft pledge            | We would like to help | Possible solution to institute challenge | Pledge Owner | draft            |
      | Some proposed pledge         | We would like to help | Possible solution to institute challenge | Pledge Owner | proposed         |
      | Some under validation pledge | We would like to help | Possible solution to institute challenge | Pledge Owner | under_validation |
      | Some validated pledge        | We would like to help | Possible solution to institute challenge | Pledge Owner | validated        |

    When I am logged in as "Pledge Owner"
    And I go to the pledge content "Some draft pledge" edit screen
    Then the following buttons should be present "Save as draft, Submit for approval"
    And the following buttons should not be present "Set as under approval, Validate"
    And I should see the link "Delete"

    # The owner cannot edit or delete the pledge in any other case.
    When I go to the pledge content "Some proposed pledge" edit screen
    Then I should see the heading "Access denied"
    When I go to the pledge content "Some proposed pledge" delete screen
    Then I should see the heading "Access denied"
    When I go to the pledge content "Some under validation pledge" edit screen
    Then I should see the heading "Access denied"
    When I go to the pledge content "Some under validation pledge" delete screen
    Then I should see the heading "Access denied"
    When I go to the pledge content "Some validated pledge" edit screen
    Then I should see the heading "Access denied"
    When I go to the pledge content "Some validated pledge" delete screen
    Then I should see the heading "Access denied"

  Scenario Outline: Moderators can perform all possible actions on the pledge.
    Given pledge content:
      | title       | description           | solution                                 | author       | state   |
      | Some pledge | We would like to help | Possible solution to institute challenge | Pledge Owner | <state> |

    When I am logged in as a moderator
    And I go to the pledge content "Some pledge" edit screen
    Then the following buttons should be present "<buttons present>"
    And the following buttons should not be present "<buttons not present>"
    And I should see the link "Delete"

    Examples:
      | state            | buttons present                                           | buttons not present                                       |
      | draft            | Save as draft, Submit for approval, Set as under approval | Update, Validate                                          |
      | proposed         | Update, Set as under approval                             | Submit for approval, Save as draft, Validate              |
      | under_validation | Update, Validate                                          | Save as draft, Submit for approval, Set as under approval |
      | validated        | Update                                                    | Save as draft, Submit for approval, Set as under approval |
