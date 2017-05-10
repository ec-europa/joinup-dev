@api
Feature:
  As a privileged user
  I want to create custom pages inside unpublished collections and solutions
  So that I can prepare all the pages before publishing them

  Scenario: Access to custom pages should be restricted to unprivileged users
    Given the following collections:
      | title           | state     |
      | Drafty things   | draft     |
      | Validated tools | validated |
    And the following solutions:
      | title               | state     |
      | Unfinished business | draft     |
      | Ready to go         | validated |
    And custom_page content:
      | title             | body         | collection      | solution            |
      | About means       | Sample text. | Drafty things   |                     |
      | About places      | Sample text. | Validated tools |                     |
      | About technology  | Sample text. |                 | Unfinished business |
      | About the weather | Sample text. |                 | Ready to go         |

    # An anonymous user can see only the custom pages of the published
    # collections/solutions.
    When I am on the homepage
    And I enter "About" in the header search bar and hit enter
    Then I should see the "About places" tile
    And I should see the "About the weather" tile
    But I should not see the "About means" tile
    And I should not see the "About technology" tile

    # Collection facilitators can see the content created inside their collections.
    When I am logged in as a facilitator of the "Drafty things" collection
    And I enter "About" in the header search bar and hit enter
    Then I should see the "About places" tile
    And I should see the "About the weather" tile
    And I should see the "About means" tile
    But I should not see the "About technology" tile

    # Solution facilitators can see the content created inside their solutions.
    When I am logged in as a facilitator of the "Unfinished business" solution
    And I enter "About" in the header search bar and hit enter
    Then I should see the "About places" tile
    And I should see the "About the weather" tile
    And I should see the "About technology" tile
    But I should not see the "About means" tile
