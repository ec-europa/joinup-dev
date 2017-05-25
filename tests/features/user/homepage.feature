@api @terms
Feature: Homepage feature
  As a registered user of the website
  when I visit the homepage of Joinup
  I want to see updates regarding the content that might be of interest to me.

  Scenario: Show content related to groups the user belongs to on the homepage.
    Given users:
      | Username     | Roles     | E-mail                   |
      | Henry Austin | Moderator | mod.murielle@example.com |
    And the following owner:
      | name        |
      | Jared Mcgee |
    And the following collections:
      | title             | description       | logo     | banner     | owner       | state     |
      | The Sacred Future | The Sacred Future | logo.png | banner.jpg | Jared Mcgee | validated |
      | Boy of Courage    | Boy of Courage    | logo.png | banner.jpg | Jared Mcgee | validated |
    And news content:
      | title                     | body                      | policy domain     | collection        | state     |
      | The Danger of the Bridges | The Danger of the Bridges | Finance in EU     | The Sacred Future | validated |
      | Girl in the Dreams        | Girl in the Dreams        | Supplier exchange | Boy of Courage    | validated |
    And the following collection user memberships:
      | collection        | user         | roles |
      | The Sacred Future | Henry Austin |       |

    When I am logged in as "Henry Austin"
    And I am on the homepage
    Then I should see the "The Danger of the Bridges" tile
    But I should not see the "Girl in the Dreams" tile
