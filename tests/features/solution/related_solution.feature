@api
Feature: Related solution
  As a visitor of the solution I should see all related solutions.

  Scenario: Related solutions
    Given the following solution:
      | title       | C                                 |
      | description | Blazing fast segmentation faults. |
      | state       | validated                         |
    Given solutions:
      | title  | description                                           | documentation | moderation | state     | related solutions | solution type  |
      | Java   | Because inheritance and boilerplate classes are cool. | text.pdf      | no         | validated | C                 | [ABB8] Citizen |
      | PHP    | Make a site.                                          | text.pdf      | yes        | validated |                   | [ABB8] Citizen |
      | Python | Get shit done                                         | text.pdf      | no         | validated |                   |                |


    # Scenario A. A collection owner manages his own collection.
    When I visit the "Java" solution
    # Referenced through EIRA building block.
    Then I see the text "PHP"
    # Direct reference.
    Then I see the text "C"
    # Nor referenced.
    And I should not see the text "Python"

