@api
Feature: Solution moderation
  In order to manage solutions programmatically
  As a user of the website
  I need to be able to transit the solutions from one state to another.

  # Access checks are not being made here. They are run in the solution add feature.
  Scenario: 'Draft' and 'Propose' states are available but moderators should also see 'Validated' state.
    Given the following collection:
      | title | Collection propose state test |
      | logo  | logo.png                      |

    When I am logged in as an "authenticated user"
    And I go to the homepage
    And I click "Propose solution"
    Then the "State" field has the "Draft, Proposed" options
    And the "State" field does not have the "Validated, In assessment, Blacklisted, Request deletion" options

    When I am logged in as a user with the "moderator" role
    And I go to the homepage
    And I click "Propose solution"
    Then the "State" field has the "Draft, Proposed, Validated" options
    And the "State" field does not have the "In assessment, Blacklisted, Request deletion" options

    When I am logged in as a "facilitator" of the "Collection propose state test" collection
    And I go to the homepage of the "Collection propose state test" collection
    And I click "Add solution"
    Then the "State" field has the "Draft, Proposed" options
    And the "State" field does not have the "Validated, In assessment, Blacklisted, Request deletion" options

  Scenario: Test the moderation workflow available states.
    Given the following organisation:
      | name | Angelos Agathe |
    And the following contact:
      | name  | Placide             |
      | email | Placide@example.com |
    And users:
      | name             | roles     |
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
      | The Guardian of the Stream | The Guardian of the Stream | logo.png | banner.jpg | Angelos Agathe | Placide             | in_assessment    |
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
    Then for the following solution, the corresponding user should have the corresponding available options:
      | solution                   | user             | states                                                 |

      # The following solutions are tested as an owner. This is for debug
      # purposed. In reality, the owner is also a facilitator so the only
      # UATable part of the owner is that he has the ability to request deletion
      # when the solution is validated.
      | Azure Ship                 | Franklin Walker  |                                                        |
      | The Last Illusion          | Franklin Walker  |                                                        |
      | Rose of Doors              | Franklin Walker  | Request deletion                                       |
      | The Ice's Secrets          | Franklin Walker  |                                                        |
      | The Guardian of the Stream | Franklin Walker  |                                                        |
      | Flames in the Swords       | Franklin Walker  |                                                        |

      # The following solutions do not follow the rule above and should be
      # testes as shown.
      | Azure Ship                 | William Curtis   | Draft, Proposed                                        |
      | The Last Illusion          | William Curtis   | Draft, Proposed                                        |
      | Rose of Doors              | William Curtis   | Draft, Proposed                                        |
      | The Ice's Secrets          | William Curtis   |                                                        |
      | The Guardian of the Stream | William Curtis   | Draft, Proposed                                        |
      | Flames in the Swords       | William Curtis   | Draft, Proposed                                        |
      | Azure Ship                 | Isabel Banks     |                                                        |
      | The Last Illusion          | Isabel Banks     |                                                        |
      | Rose of Doors              | Isabel Banks     |                                                        |
      | The Ice's Secrets          | Isabel Banks     |                                                        |
      | The Guardian of the Stream | Isabel Banks     |                                                        |
      | Flames in the Swords       | Isabel Banks     |                                                        |
      | Azure Ship                 | Tyrone Underwood | Draft, Proposed, Validated                             |
      | The Last Illusion          | Tyrone Underwood | Draft, Proposed, Validated, In assessment              |
      | Rose of Doors              | Tyrone Underwood | Draft, Proposed, Validated, In assessment, Blacklisted |
      | The Ice's Secrets          | Tyrone Underwood | Draft, Proposed, Validated                             |
      | The Guardian of the Stream | Tyrone Underwood | Draft, Proposed, Validated                             |
      | Flames in the Swords       | Tyrone Underwood | Draft, Proposed, Validated                             |

    # Authentication sample checks.
    Given I am logged in as "William Curtis"

    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "Draft, Proposed" options
    And the "State" field does not have the "Validated, In assessment, Blacklisted, Request deletion" options

    # Expected access denied.
    When I go to the "The Last Illusion" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "Draft, Proposed" options
    And the "State" field does not have the "Validated, In assessment, Blacklisted, Request deletion" options

    # One check for the moderator.
    Given I am logged in as "Tyrone Underwood"
    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "Draft, Proposed, Validate" options
