@api
Feature: Document moderation
  In order to manage documents
  As a user of the website
  I need to be able to transit the documents from one state to another.

  Scenario: Available transitions change per eLibrary and moderation settings.
    Given users:
      | name            |
      | Crab y Patties  |
      | Gabe Rogers     |
      | Brigham Salvage |
    And the following owner:
      | name          |
      | thisisanowner |
    And the following collection:
      | title             | DIY collection                           |
      | description       | Collection of "Do it yourself" projects. |
      | logo              | logo.png                                 |
      | banner            | banner.jpg                               |
      | elibrary creation | registered users                         |
      | moderation        | no                                       |
      | state             | validated                                |
      | owner             | thisisanowner                            |
      | policy domain     | Demography and population                |
    And the following collection user membership:
      | collection     | user            | roles       |
      | DIY collection | Gabe Rogers     | member      |
      | DIY collection | Brigham Salvage | facilitator |

    # For post moderated collections with eLibrary set to allow all users, even
    # authenticated users can create content.
    When I am logged in as "Crab y Patties"
    And go to the homepage of the "DIY collection" collection
    And I click "Add document" in the plus button menu
    # Post moderated collections allow publishing content directly.
    And I should see the button "Publish"

    # Changing settings to the collection should affect the allowed transitions.
    When I am logged in as a moderator
    And I go to the homepage of the "DIY collection" collection
    And I click "Edit" in the "Entity actions" region
    And I check the box "Moderated"
    Then I press "Publish"
    And I should see the heading "DIY collection"

    # The authenticated user should still be able to create content but not
    # publish it.
    When I am logged in as "Crab y Patties"
    And I go to the homepage of the "DIY collection" collection
    And I click "Add document" in the plus button menu
    Then I should not see the button "Publish"
    But I should see the button "Save as draft"
    And I should see the button "Request approval"

    # The eLibrary should block access for specific users.
    When I am logged in as a moderator
    And I go to the homepage of the "DIY collection" collection
    And I click "Edit" in the "Entity actions" region
    And I check "Closed collection"
    And I select "Only collection facilitators can create new content" from "eLibrary creation"
    Then I press "Publish"
    And I should see the link "Add document"

    # The authenticated user should still be able to create content but not
    # publish it.
    When I am logged in as "Crab y Patties"
    And I go to the homepage of the "DIY collection" collection
    And I should not see the link "Add document"
