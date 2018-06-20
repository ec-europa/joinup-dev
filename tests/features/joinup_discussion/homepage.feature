@api
Feature: Discussion homepage
  As a user of the website
  I want to be able to view the discussion information
  In order to have an overview of its content

  Scenario: The discussion homepage should show the collections where it is shared in.
    Given the following collections:
      | title                         | description       | state     |
      | Development through bricolage | Bricolage 101.    | validated |
      | Carrots love tomatoes         | Truth is out now. | validated |
    And discussion content:
      | title         | state     | collection                    | shared in             |
      | Tools cabinet | validated | Development through bricolage | Carrots love tomatoes |

    When I go to the "Tools cabinet" discussion
    Then I should see the text "Shared in"
    And I should see the "Carrots love tomatoes" tile
