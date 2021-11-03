@api @group-d
Feature: Discussion homepage
  As a user of the website
  I want to be able to view the discussion information
  In order to have an overview of its content

  Scenario: The discussion homepage should show the collections where it is shared on.
    Given the following collections:
      | title                         | description       | state     |
      | Development through bricolage | Bricolage 101.    | validated |
      | Carrots love tomatoes         | Truth is out now. | validated |
    And discussion content:
      | title         | state     | collection                    | shared on             |
      | Tools cabinet | validated | Development through bricolage | Carrots love tomatoes |

    When I go to the "Tools cabinet" discussion
    Then I should see the text "Shared on"
    And I should see the "Carrots love tomatoes" tile
