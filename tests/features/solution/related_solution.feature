@api @terms
Feature: Related solution
  As a solution facilitator, I need to be able to relate my solution to other
  solutions and present the related solutions to the users.

  Scenario: Related solutions
    Given the following contact:
      | email | bar@bar.com |
      | name  | Kalikatoura |
    And the following owner:
      | name         | type                         |
      | Kalikatoures | Company, Industry consortium |
    And solutions:
      | title  | related solutions | description                      | documentation | moderation | logo     | banner     | policy domain | state     | solution type  | owner        | contact information |
      | C      |                   | Blazing fast segmetation faults. | text.pdf      | no         | logo.png | banner.jpg | Demography    | validated |                | Kalikatoures | Kalikatoura         |
      | Java   | C                 | Because inheritance is cool.     | text.pdf      | no         | logo.png | banner.jpg | Demography    | validated | [ABB8] Citizen | Kalikatoures | Kalikatoura         |
      | PHP    |                   | Make a site.                     | text.pdf      | yes        | logo.png | banner.jpg | Demography    | validated | [ABB8] Citizen | Kalikatoures | Kalikatoura         |
      | Python |                   | Get stuff done.                  | text.pdf      | no         | logo.png | banner.jpg | Demography    | validated |                | Kalikatoures | Kalikatoura         |

    # Scenario A. A collection owner manages his own collection.
    When I visit the "Java" solution
    # Referenced through EIRA building block.
    Then I see the "PHP" tile
    # Direct reference.
    And I see the "C" tile
    # Not referenced.
    And I should not see the "Python" tile

    # Relate two solutions.
    When I am logged in as a facilitator of the "Java" solution
    And I visit the "Java" solution
    And I click "Edit" in the "Entity actions" region
    And I fill in "Related Solutions" with values "C, Python"
    And I press "Propose"
    Then I should see the heading "Java"
    # The solution is not published yet.
    But I should not see the "Python" tile

    # Publish the changes.
    When I am logged in as a moderator
    And I visit the "Java" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    And I visit the "Java" solution
    Then I should see the "Python" tile
