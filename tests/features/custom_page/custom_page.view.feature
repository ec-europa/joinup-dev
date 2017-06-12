@api
Feature:
  As a privileged user
  I want to create custom pages inside unpublished collections
  So that I can prepare all the pages before publishing them

  Scenario: Access to custom pages should be restricted to unprivileged users
    Given the following collections:
      | title           | state     |
      | Drafty things   | draft     |
      | Validated tools | validated |
    And custom_page content:
      | title        | body         | collection      |
      | About means  | Sample text. | Drafty things   |
      | About places | Sample text. | Validated tools |

    # An anonymous user can see only the custom pages of the published
    # collections.
    When I am on the homepage
    And I enter "About" in the header search bar and hit enter
    Then I should see the "About places" tile
    But I should not see the "About means" tile

    # Collection facilitators can see the content created inside their collections
    # but not in the search because the content are not indexed.
    When I am logged in as a facilitator of the "Drafty things" collection
    And I enter "About" in the header search bar and hit enter
    Then I should see the "About places" tile
    And I should not see the "About means" tile
