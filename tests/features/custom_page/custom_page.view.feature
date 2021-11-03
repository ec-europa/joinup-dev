@api @group-f
Feature:
  As a privileged user
  I want to create custom pages inside unpublished collections or solutions
  So that I can prepare all the pages before publishing them

  Scenario Outline: Access to custom pages should be restricted to unprivileged users
    Given the following <group>s:
      | title           | state     |
      | Drafty things   | draft     |
      | Validated tools | validated |
    And custom_page content:
      | title        | body         | collection      | logo     |
      | About means  | Sample text. | Drafty things   | logo.png |
      | About places | Sample text. | Validated tools | logo.png |

    # An anonymous user can see only the custom pages of the published
    # groups.
    When I am on the homepage
    And I enter "About" in the search bar and press enter
    Then I should see the "About places" tile
    But I should not see the "About means" tile

    # Facilitators can see the content created inside their respective group
    # but not in the search because the content are not indexed.
    When I am logged in as a facilitator of the "Drafty things" <group>
    And I enter "About" in the search bar and press enter
    Then I should see the "About places" tile
    And I should not see the "About means" tile

    When I go to the homepage of the "Drafty things" <group>
    Then I should see the "About means" tile
    And the logo should be shown in the "About means" custom page tile

    Examples:
      | group      |
      | collection |
      | solution   |
