@api
Feature: Solution moderation
  In order to manage solutions programmatically
  As a user of the website
  I need to be able to transit the solutions from one state to another.

  # Access checks are not being made here. They are run in the solution add feature.
  Scenario: 'Save as draft' and 'Propose' states are available but moderators should also see 'Publish' state.
    Given the following collection:
      | title | Collection propose state test |
      | logo  | logo.png                      |
      | state | validated                     |

    When I am logged in as an "authenticated user"
    And I go to the add solution form
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist, Request deletion"

    When I am logged in as a user with the "moderator" role
    And I go to the add solution form
    Then the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request changes, Blacklist, Request deletion"

    When I am logged in as a "facilitator" of the "Collection propose state test" collection
    And I go to the homepage of the "Collection propose state test" collection
    And I click "Add solution"
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist, Request deletion"

  Scenario: Test the moderation workflow available states.
    Given the following owner:
      | name           | type                  |
      | Angelos Agathe | Private Individual(s) |
    And the following contact:
      | name  | Placide             |
      | email | Placide@example.com |
    And users:
      | Username         | Roles     |
      # Authenticated user.
      | Isabel Banks     |           |
      # Moderator.
      | Tyrone Underwood | moderator |
      # Owner of all the solutions.
      | Franklin Walker  |           |
      # Facilitator of all the solutions.
      | William Curtis   |           |
    And the following solutions:
      | title                      | description                | logo     | banner     | owner          | contact information | state            |
      | Azure Ship                 | Azure ship                 | logo.png | banner.jpg | Angelos Agathe | Placide             | draft            |
      | The Last Illusion          | The Last Illusion          | logo.png | banner.jpg | Angelos Agathe | Placide             | proposed         |
      | Rose of Doors              | Rose of Doors              | logo.png | banner.jpg | Angelos Agathe | Placide             | validated        |
      | The Ice's Secrets          | The Ice's Secrets          | logo.png | banner.jpg | Angelos Agathe | Placide             | deletion_request |
      | The Guardian of the Stream | The Guardian of the Stream | logo.png | banner.jpg | Angelos Agathe | Placide             | needs_update     |
      | Flames in the Swords       | Flames in the Swords       | logo.png | banner.jpg | Angelos Agathe | Placide             | blacklisted      |
    And the following solution user memberships:
      | solution                   | user            | roles       |
      | Azure Ship                 | Franklin Walker | owner       |
      | The Last Illusion          | Franklin Walker | owner       |
      | Rose of Doors              | Franklin Walker | owner       |
      | The Ice's Secrets          | Franklin Walker | owner       |
      | The Guardian of the Stream | Franklin Walker | owner       |
      | Flames in the Swords       | Franklin Walker | owner       |
      | Azure Ship                 | William Curtis  | facilitator |
      | The Last Illusion          | William Curtis  | facilitator |
      | Rose of Doors              | William Curtis  | facilitator |
      | The Ice's Secrets          | William Curtis  | facilitator |
      | The Guardian of the Stream | William Curtis  | facilitator |
      | Flames in the Swords       | William Curtis  | facilitator |

    # The following table tests the allowed transitions in a solution.
    # For each entry, the following steps must be performed:
    # Login with the given user (or a user with the same permissions).
    # Go to the homepage of the given solution.
    # If the expected states (states column) are empty, I should not have access
    # to the edit screen.
    # If the expected states are not empty, then I see the "Edit" link.
    # When I click the "Edit" link
    # Then the state field should have only the given states available.
    Then for the following solution, the corresponding user should have the corresponding available state buttons:
      | solution                   | user             | states                                                      |

      # The following solutions are tested as an owner. This is for debug
      # purposed. In reality, the owner is also a facilitator so the only
      # UATable part of the owner is that he has the ability to request deletion
      # when the solution is validated.
      | Azure Ship                 | Franklin Walker  | Save as draft, Propose                                      |
      | The Last Illusion          | Franklin Walker  | Save as draft, Propose                                      |
      | Rose of Doors              | Franklin Walker  | Save as draft, Propose, Request deletion                    |
      | The Ice's Secrets          | Franklin Walker  |                                                             |
      | The Guardian of the Stream | Franklin Walker  | Save as draft, Propose                                      |
      | Flames in the Swords       | Franklin Walker  | Save as draft, Propose                                      |

      # The following solutions do not follow the rule above and should be
      # testes as shown.
      | Azure Ship                 | William Curtis   | Save as draft, Propose                                      |
      | The Last Illusion          | William Curtis   | Save as draft, Propose                                      |
      | Rose of Doors              | William Curtis   | Save as draft, Propose                                      |
      | The Ice's Secrets          | William Curtis   |                                                             |
      | The Guardian of the Stream | William Curtis   | Save as draft, Propose                                      |
      | Flames in the Swords       | William Curtis   | Save as draft, Propose                                      |
      | Azure Ship                 | Isabel Banks     |                                                             |
      | The Last Illusion          | Isabel Banks     |                                                             |
      | Rose of Doors              | Isabel Banks     |                                                             |
      | The Ice's Secrets          | Isabel Banks     |                                                             |
      | The Guardian of the Stream | Isabel Banks     |                                                             |
      | Flames in the Swords       | Isabel Banks     |                                                             |
      | Azure Ship                 | Tyrone Underwood | Save as draft, Propose, Publish                             |
      | The Last Illusion          | Tyrone Underwood | Save as draft, Propose, Publish, Request changes            |
      | Rose of Doors              | Tyrone Underwood | Save as draft, Propose, Publish, Request changes, Blacklist |
      | The Ice's Secrets          | Tyrone Underwood | Save as draft, Propose, Publish                             |
      | The Guardian of the Stream | Tyrone Underwood | Save as draft, Propose, Publish                             |
      | Flames in the Swords       | Tyrone Underwood | Save as draft, Propose, Publish                             |

    # Authentication sample checks.
    Given I am logged in as "William Curtis"

    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist, Request deletion"

    # Expected access denied.
    When I go to the "The Last Illusion" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist, Request deletion"

    # One check for the moderator.
    Given I am logged in as "Tyrone Underwood"
    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request changes, Blacklist, Request deletion"
