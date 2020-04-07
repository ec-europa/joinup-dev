@api @group-a
Feature: Ensure that redirect cache invalidation is working properly.

  @terms
  Scenario: Redirect cache is invalidated for anonymous users when publishing a
  collection.

    Given owner:
      | name          | type    |
      | Cache company | Company |

    When I am logged in as a moderator
    And I go to the propose collection form
    And I fill in the following:
      | Title                 | Cache debug collection      |
      | Description           | Description does not matter |
      | Geographical coverage | Belgium                     |
      | Name                  | Cache manager               |
      | E-mail                | cache_manager@example.com   |
    And I select "HR" from "Policy domain"
    And I select the radio button "Only members can create content."
    And I check "Moderated"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Cache company"
    And I press "Propose"
    Then I should see the heading "Cache debug collection"

    When I am not logged in
    And I go to the "Cache debug collection" collection
    Then I should see the heading "Sign in to continue"

    When I am logged in as a moderator
    And I go to the "Cache debug collection" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then I should see the heading "Cache debug collection"

    When I am not logged in
    And I go to the "Cache debug collection" collection
    Then I should see the heading "Cache debug collection"

    # Cache invalidation occurring properly.
    And I delete the "Cache debug collection" collection
