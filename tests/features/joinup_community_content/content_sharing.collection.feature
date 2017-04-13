@api
Feature: Sharing content between collections
  As a privileged user
  I want to share content between collections
  So that useful information has more visibility

  Scenario Outline: Users can share content in the collections they are member of.
    Given users:
      | Username      | E-mail                    |
      | Sara Barber   | sara.barber@example.com   |
      | Deryck Lynn   | deryck.lynn@example.com   |
      | Marjolein Rye | marjolein.rye@example.com |
    And the following collections:
      | title        | state     |
      | Classic Rock | validated |
      | Hip-Hop      | validated |
      | Power ballad | validated |
      | Drum'n'Bass  | validated |
    And news content:
      | title                          | kicker                         | collection   | status    |
      | New D'n'B compilation released | New D'n'B compilation released | Classic Rock | published |
      | Old-school line-up concert     | Old-school line-up concert     | Hip-Hop      | published |
    And discussion content:
      | title                       | collection   | status    |
      | Rockabilly is still rocking | Classic Rock | published |
    And the following collection user memberships:
      | collection   | user          |
      | Hip-Hop      | Marjolein Rye |
      | Classic Rock | Sara Barber   |
      | Hip-Hop      | Sara Barber   |
      | Drum'n'Bass  | Sara Barber   |
    And <content type> content:
      | title               | collection | status    |
      | Interesting content | Hip-Hop    | published |

    # Anonymous users cannot share anything.
    When I am an anonymous user
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    Then I should not see the link "Share"
    # This "authenticated user" is not member of any collections.
    When I am logged in as an "authenticated user"
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    Then I should not see the link "Share"

    # A member of a single collection shouldn't see the link.
    When I am logged in as "Marjolein Rye"
    And I go to the content page of the type "<content type>" with the title "Interesting content"
    Then I should not see the link "Share"

    # A collection member should see the link.
    When I am logged in as "Sara Barber"
    And I go to the "Rockabilly is still rocking" discussion
    Then I should see the link "Share"
    When I click "Share"
    Then I should see the heading "Share"
    # Collections the user is member of should be available.
    And the following fields should be present "Hip-Hop, Drum'n'Bass"
    # While the original content collection and collections the user is not
    # member of should not be shown.
    But the following fields should not be present "Classic Rock, Power ballad"

    # Verify on another node the correctness of the share tool.
    When I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then I should see the heading "Share"
    And the following fields should be present "Classic Rock, Drum'n'Bass"
    And the following fields should not be present "Hip-Hop, Power ballad"

    # Share the content in a collection.
    When I check "Classic Rock"
    And I press "Save"
    Then I should see the success message "Sharing updated."
    And the "Classic Rock" checkbox should be checked
    And the "Drum'n'Bass" checkbox should not be checked

    # The shared content should be shown amongst the other content tiles.
    When I go to the homepage of the "Classic Rock" collection
    Then I should see the "New D'n'B compilation released" tile
    And I should see the "Rockabilly is still rocking" tile
    And I should see the "Interesting content" tile

    # It should not be shared in the other collection.
    When I go to the homepage of the "Drum'n'Bass" collection
    Then I should not see the "Interesting content" tile

    # Un-share the content.
    When I go to the content page of the type "<content type>" with the title "Interesting content"
    And I click "Share"
    Then I should see the heading "Share"
    And I uncheck "Classic Rock"
    And I press "Save"
    Then I should see the success message "Sharing updated."

    # Verify that the collection content has been updated.
    When I go to the homepage of the "Classic Rock" collection
    Then I should see the "New D'n'B compilation released" tile
    And I should see the "Rockabilly is still rocking" tile
    But I should not see the "Interesting content" tile

    # The content should obviously not shared in the other collection too.
    When I go to the homepage of the "Drum'n'Bass" collection
    Then I should not see the "Interesting content" tile

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |
