@api
Feature: Revision permissions support in OG
  As a privileged user
  In order to be able to see changes between revisions of my groups content
  I need to be able to access the revision-related pages inside my groups

  Scenario: Facilitators can access the revision-related pages.
    Given users:
      | name         | mail                     |
      | Ainslee Hext | ainslee.hext@example.com |
      | Erik Quick   | erik.quick@example.com   |
    And the following collection:
      | title | Mechanics 101 |
      | state | validated     |
    And the following solution:
      | title | Carpenters DIY |
      | state | validated      |
    And collection user membership:
      | collection    | user         |
      | Mechanics 101 | Ainslee Hext |
    And solution user membership:
      | solution       | user       |
      | Carpenters DIY | Erik Quick |
    And custom_page content:
      | title         | body                          | collection    |
      | Mechanics FAQ | Common questions and answers. | Mechanics 101 |
    # Create a discussion in the collection.
    And discussion content:
      | title                             | body           | collection    | author       |
      | Open-ended or open-ring spanners? | Pros and cons. | Mechanics 101 | Ainslee Hext |
    # And one in the solution.
    And discussion content:
      | title                                | body                            | solution       | author     |
      | Orbital sander tearing off too fast? | Any tricks to improve lifespan? | Carpenters DIY | Erik Quick |
    And discussion revisions:
      | current title                        | body                                               |
      | Open-ended or open-ring spanners?    | Positive and negative aspects.                     |
      | Orbital sander tearing off too fast? | Any tricks to improve lifespan of the tool itself? |

    # Collection members cannot access the list of revisions, even if they are the authors.
    When I am logged in as "Ainslee Hext"
    And I go to the "Open-ended or open-ring spanners?" discussion
    Then I should not see the link "Revisions"
    # Same goes for solution members.
    When I am logged in as "Erik Quick"
    And I go to the "Orbital sander tearing off too fast?" discussion
    Then I should not see the link "Revisions"

    # Verify that collection facilitator has the permission to see revisions.
    When I am logged in as a facilitator of the "Mechanics 101" collection
    And I go to the "Mechanics FAQ" custom page
    # The custom page has only one revision, so the revisions link should not be visible yet.
    Then I should not see the link "Revisions"
    # Create a revision.
    When I click "Edit"
    And I enter "Common and not-so-common questions and answers." in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the link "Revisions"
    # Verify the access to the pages.
    When I click "Revisions"
    Then I should see the heading "Revisions for Mechanics FAQ"
    When I press "Compare selected revisions"
    Then I should see the heading "Changes to Mechanics FAQ"
    And I should see the text "Common questions and answers."
    And I should see the text "Common and not-so-common questions and answers."

    # The solution facilitator has the same permissions on revisions.
    When I am logged in as a facilitator of the "Carpenters DIY" solution
    And I go to the "Orbital sander tearing off too fast?" discussion
    Then I should see the link "Revisions"
    When I click "Revisions"
    Then I should see the heading "Revisions for Orbital sander tearing off too fast?"
    When I press "Compare selected revisions"
    Then I should see the heading "Changes to Orbital sander tearing off too fast?"
    And I should see the text "Any tricks to improve lifespan?"
    And I should see the text "Any tricks to improve lifespan of the tool itself?"
