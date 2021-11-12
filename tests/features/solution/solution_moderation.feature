@api @group-f
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

    When I am logged in as a member of the "Collection propose state test" collection
    And I go to the add solution form of the "Collection propose state test" collection
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist"
    And I should not see the link "Delete"

    When I am logged in as a user with the "moderator" role
    And I go to the add solution form of the "Collection propose state test" collection
    Then the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request changes, Blacklist"
    And I should not see the link "Delete"

    When I am logged in as a "facilitator" of the "Collection propose state test" collection
    And I go to the homepage of the "Collection propose state test" collection
    And I click "Add solution"
    And I check "I have read and accept the legal notice and I commit to manage my solution on a regular basis."
    And I press "Yes"
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist"
    And I should not see the link "Delete"

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
      | title                      | description                | logo     | banner     | owner          | contact information | state        |
      | Azure Ship                 | Azure ship                 | logo.png | banner.jpg | Angelos Agathe | Placide             | draft        |
      | The Last Illusion          | The Last Illusion          | logo.png | banner.jpg | Angelos Agathe | Placide             | proposed     |
      | Rose of Doors              | Rose of Doors              | logo.png | banner.jpg | Angelos Agathe | Placide             | validated    |
      | The Guardian of the Stream | The Guardian of the Stream | logo.png | banner.jpg | Angelos Agathe | Placide             | needs update |
      | Flames in the Swords       | Flames in the Swords       | logo.png | banner.jpg | Angelos Agathe | Placide             | blacklisted  |
    And the following solution user memberships:
      | solution                   | user            | roles       |
      | Azure Ship                 | Franklin Walker | owner       |
      | The Last Illusion          | Franklin Walker | owner       |
      | Rose of Doors              | Franklin Walker | owner       |
      | The Guardian of the Stream | Franklin Walker | owner       |
      | Flames in the Swords       | Franklin Walker | owner       |
      | Azure Ship                 | William Curtis  | facilitator |
      | The Last Illusion          | William Curtis  | facilitator |
      | Rose of Doors              | William Curtis  | facilitator |
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
      | solution                   | user             | buttons                                                     |

      # The following solutions are tested as an owner. In reality, the owner is
      # also a facilitator so the only ability that differentiates the owner
      # from a facilitator is that they have the ability to delete their
      # solution when the solution is validated. Note that the "Delete" option
      # is a link to a confirmation form which is styled to look as a button.
      # This is checked separately below.
      | Azure Ship                 | Franklin Walker  | Save as draft, Propose                                      |
      | The Last Illusion          | Franklin Walker  | Propose, Save as draft                                      |
      | Rose of Doors              | Franklin Walker  | Publish, Save as draft, Propose                             |
      | The Guardian of the Stream | Franklin Walker  | Save as draft, Propose                                      |
      | Flames in the Swords       | Franklin Walker  | Save as draft, Propose                                      |

      # The following solutions do not follow the rule above and should be
      # testes as shown.
      | Azure Ship                 | William Curtis   | Save as draft, Propose                                      |
      | The Last Illusion          | William Curtis   | Propose, Save as draft                                      |
      | Rose of Doors              | William Curtis   | Publish, Save as draft, Propose                             |
      | The Guardian of the Stream | William Curtis   | Save as draft, Propose                                      |
      | Flames in the Swords       | William Curtis   | Save as draft, Propose                                      |
      | Azure Ship                 | Isabel Banks     |                                                             |
      | The Last Illusion          | Isabel Banks     |                                                             |
      | Rose of Doors              | Isabel Banks     |                                                             |
      | The Guardian of the Stream | Isabel Banks     |                                                             |
      | Flames in the Swords       | Isabel Banks     |                                                             |
      | Azure Ship                 | Tyrone Underwood | Save as draft, Propose, Publish                             |
      | The Last Illusion          | Tyrone Underwood | Propose, Save as draft, Publish, Request changes            |
      | Rose of Doors              | Tyrone Underwood | Publish, Save as draft, Propose, Request changes, Blacklist |
      | The Guardian of the Stream | Tyrone Underwood | Save as draft, Propose, Publish                             |
      | Flames in the Swords       | Tyrone Underwood | Save as draft, Propose, Publish                             |

    # The 'Delete' action is not a button but a link leading to a confirmation
    # page that is styled as a button. It should only be available to the owner
    # and a moderator.
    And the visibility of the delete link should be as follows for these users in these solutions:
      | solution                   | user             | delete link |
      | Azure Ship                 | Franklin Walker  | no          |
      | The Last Illusion          | Franklin Walker  | no          |
      | Rose of Doors              | Franklin Walker  | yes         |
      | The Guardian of the Stream | Franklin Walker  | no          |
      | Flames in the Swords       | Franklin Walker  | no          |
      | Azure Ship                 | William Curtis   | no          |
      | The Last Illusion          | William Curtis   | no          |
      | Rose of Doors              | William Curtis   | no          |
      | The Guardian of the Stream | William Curtis   | no          |
      | Flames in the Swords       | William Curtis   | no          |
      | Azure Ship                 | Isabel Banks     | no          |
      | The Last Illusion          | Isabel Banks     | no          |
      | Rose of Doors              | Isabel Banks     | no          |
      | The Guardian of the Stream | Isabel Banks     | no          |
      | Flames in the Swords       | Isabel Banks     | no          |
      | Azure Ship                 | Tyrone Underwood | yes         |
      | The Last Illusion          | Tyrone Underwood | yes         |
      | Rose of Doors              | Tyrone Underwood | yes         |
      | The Guardian of the Stream | Tyrone Underwood | yes         |
      | Flames in the Swords       | Tyrone Underwood | yes         |

    # Authentication sample checks.
    Given I am logged in as "William Curtis"

    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist"
    And I should not see the link "Delete"

    # Expected access denied.
    When I go to the "The Last Illusion" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Blacklist"
    And I should not see the link "Delete"

    # One check for the moderator.
    Given I am logged in as "Tyrone Underwood"
    # Expected access.
    And I go to the "Azure Ship" solution
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request changes, Blacklist"
    And I should see the link "Delete"
