# @todo Remove the whole feature when a workflow for news will be in place.
@api
Feature: "Add news" link visibility is temporary disabled on solution

  Scenario: "Add news" button should not be shown at all.
    Given the following solutions:
      | title           | logo     | banner     |
      | Ragged Tower    | logo.png | banner.jpg |
      | Prince of Magic | logo.png | banner.jpg |
    And the following collection:
      | title      | Collective Ragged tower       |
      | logo       | logo.png                      |
      | banner     | banner.jpg                    |
      | affiliates | Ragged Tower, Prince of Magic |

    When I am an anonymous user
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add news"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add news"

    When I am logged in as a "facilitator" of the "Ragged Tower" solution
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add news"
    # I should not be able to add a news to a different solution
    When I go to the homepage of the "Prince of Magic" solution
    Then I should not see the link "Add news"

    When I am logged in as a "moderator"
    And I go to the homepage of the "Ragged Tower" solution
    Then I should not see the link "Add news"
