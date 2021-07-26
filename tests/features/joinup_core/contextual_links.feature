@api @group-b
Feature:
  As a moderator of the website
  in order to better manage all content
  I need to be able to access operations directly through the contextual links.

  @terms
  Scenario: Revisions link availability.
    Given community:
      | title | Revisions community |
      | state | validated            |
    When I am logged in as a moderator
    And I go to the "Revisions community" community
    And I click "Add news" in the plus button menu
    And I fill in the following:
      | Short title | Revisions community published          |
      | Headline    | Revisions community has been published |
    And I enter "We are proud to announce another useless test entity." in the "Content" wysiwyg editor
    And I select "EU and European Policies" from "Topic"
    And I press "Save as draft"

    # Edit the news to create a new revision.
    And I click "Edit" in the "Entity actions" region
    And I enter "We are proud (well not that proud) to announce another useless test entity." in the "Content" wysiwyg editor
    And I press "Save as draft"

    And I click "Revisions" in the "Entity actions" region
    And I click the last "Delete" link
    And I press "Delete"
    Then I should see the heading "Revisions community has been published"
    But I should not see the link "Revisions" in the "Entity actions" region

    When I am logged in as a facilitator of the "Revisions community" community
    And I go to the homepage of the "Revisions community" community
    Then I should not see the link "Revisions" in the "Entity actions" region

  @javascript
  Scenario: Only solutions should have the share contextual link available.
    Given community:
      | title | Share community |
      | state | validated        |
    And contact:
      | name  | Somebody             |
      | email | somebody@example.com |
    And owner:
      | name       |
      | Some owner |
    And solution:
      | title               | Share solution   |
      | state               | validated        |
      | collection          | Share community |
      | contact information | Somebody         |
      | owner               | Some owner       |

    When I am logged in as a user with the "moderator" role
    And I go to the "Share solution" solution
    And I click "About"
    Then I should not see the contextual link "Share"

    When I click "Communities"
    Then I should not see the contextual link "Share"
