@api @group-f
Feature: As a privileged user
  I want to share solutions to my collections
  So that useful information has more visibility

  @javascript
  Scenario: Share link is visible for privileged users.
    Given users:
      | Username    | E-mail                  | First name | Family name | Roles     |
      | joe_dare    | joe_dare@example.com    | Joe        | Dare        |           |
      | kleev_elant | kleev_elant@example.com | Kleev      | Elant       |           |
      | sand_beach  | sand_beach@example.com  | Sand       | Beach       | moderator |
    And collections:
      | title                        | logo     | state     |
      | Collection share original    | logo.png | validated |
      | Collection share candidate 1 | logo.png | validated |
      | Collection share candidate 2 | logo.png | validated |
    And the following solutions:
      | title                 | description         | logo     | banner     | state     | collection                |
      | Solution to be shared | Doesn't affect test | logo.png | banner.jpg | validated | Collection share original |
    And the following solution user memberships:
      | solution              | user        | roles       |
      | Solution to be shared | joe_dare    | owner       |
      | Solution to be shared | kleev_elant | facilitator |

    Given I am an anonymous user
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    But I should not see the contextual link "Share" in the "Solution to be shared" tile
    And I should not see the contextual link "Unshare" in the "Solution to be shared" tile

    Given I am logged in as a moderator
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Share" in the "Solution to be shared" tile
    But I should not see the contextual link "Unshare" in the "Solution to be shared" tile
    When I click the contextual link "Share" in the "Solution to be shared" tile
    Then a modal should open
    # The moderator has access to the modal but no collections are available.
    And the following fields should not be present "Collection share original, Collection share candidate 1, Collection share candidate 2"

    Given I am logged in as a member of the "Collection share candidate 1" collection
    And I go to the homepage of the "Collection share original" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Share" in the "Solution to be shared" tile
    But I should not see the contextual link "Unshare" in the "Solution to be shared" tile
    When I click the contextual link "Share" in the "Solution to be shared" tile
    Then a modal should open
    And the following fields should be present "Collection share candidate 1"
    And the following fields should not be present "Collection share original, Collection share candidate 2"

    # Share the solution into a collection.
    When I check "Collection share candidate 1"
    And I press "Share" in the "Modal buttons" region
    And I wait for AJAX to finish
    Then I should see the success message "Item was shared on the following groups: Collection share candidate 1."

    # Verify that the collections where the solution has already been shared are
    # not shown anymore in the list.
    And I go to the homepage of the "Collection share original" collection
    When I click the contextual link "Share" in the "Solution to be shared" tile
    Then a modal should open
    And the following fields should not be present "Collection share original, Collection share candidate 1, Collection share candidate 2"

    # The shared solution should be shown amongst the other content tiles.
    When I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And the "Solution to be shared" tile should be marked as shared from "Collection share original"

    # It should not be shared on the other collection.
    When I go to the homepage of the "Collection share candidate 2" collection
    Then I should not see the "Solution to be shared" tile

    # Solutions can be un-shared only by facilitators of the collections they
    # have been shared on.
    When I am an anonymous user
    And I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And I should not see the contextual link "Unshare" in the "Solution to be shared" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And I should not see the contextual link "Unshare" in the "Solution to be shared" tile

    # Facilitators of other collections cannot unshare from the specific collection.
    When I am logged in as a facilitator of the "Collection share candidate 2" collection
    And I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And I should not see the contextual link "Unshare" in the "Solution to be shared" tile

    When I am logged in as a moderator
    And I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Unshare" in the "Solution to be shared" tile

    When I am logged in as a facilitator of the "Collection share candidate 1" collection
    And I go to the homepage of the "Collection share candidate 1" collection
    Then I should see the "Solution to be shared" tile
    And I should see the contextual link "Unshare" in the "Solution to be shared" tile
    When I click the contextual link "Unshare" in the "Solution to be shared" tile
    Then a modal will open
    And I should see the text "Unshare Solution to be shared from"
    Then the following fields should be present "Collection share candidate 1"
    And the following fields should not be present "Collection share original, Collection share candidate 2"

    # Unshare the content.
    When I check "Collection share candidate 1"
    And I press "Submit" in the "Modal buttons" region
    And I wait for AJAX to finish

    # I should still be on the same page, but the collection content should be
    # changed. The "Solution to be shared" should no longer be visible.
    Then I should see the success message "Item was unshared from the following groups: Collection share candidate 1."
    And I should not see the "Solution to be shared" tile

    # Verify that the content is again shareable.
    And I go to the homepage of the "Collection share original" collection
    # Verify that the unshare link is not present when the content is not
    # shared anywhere.
    Then I should not see the contextual link "Unshare" in the "Solution to be shared" tile

    When I click the contextual link "Share" in the "Solution to be shared" tile
    Then a modal should open
    And the following fields should be present "Collection share candidate 1"
    And the following fields should not be present "Collection share original, Collection share candidate 2"

    # The content should obviously not shared on the other collection too.
    When I go to the homepage of the "Collection share candidate 2" collection
    Then I should not see the "Solution to be shared" tile
