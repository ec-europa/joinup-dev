@api
Feature:
  As a moderator
  In order to better organize the content
  I need to be able to move content between groups

  Scenario: Use the content management UI

    Given the following collections:
      | title                  | state     |
      | Source collection      | validated |
      | Destination collection | validated |
    And document content:
      | title      | document type | collection        |
      | A document | Document      | Source collection |
    And discussion content:
      | title             | collection        |
      | Just a discussion | Source collection |
    And event content:
      | title           | collection        |
      | Wonderful event | Source collection |
    And news content:
      | title         | collection        |
      | Breaking news | Source collection |
    And custom_page content:
      | title   | collection        |
      | Section | Source collection |

    Given I am an anonymous user
    And I go to the homepage of the "Source collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as an "authenticated user"
    And I go to the homepage of the "Source collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a facilitator of the "Source collection" collection
    And I go to the homepage of the "Source collection" collection
    Then I should not see the link "Manage content"

    Given I am logged in as a moderator
    And I go to the homepage of the "Source collection" collection
    Then I should see the link "Manage content"

    Given I click "Manage content"
    Then I should see "Manage content" in the Header

    # Select rows.
    Given I select the "A document" row
    And I select the "Just a discussion" row
    And I select the "Wonderful event" row
    And I select the "Breaking news" row
    And I select the "Section" row

    # Select the action.
    Given I select "Move to other group" from "Action"
    And I press "Apply to selected items"

    Then I fill in "Select the destination collection or solution" with "Destination collection"
    And I press "Apply"

    # Run the batch process.
    Given I wait for the batch process to finish

    Then I should see "The group of Document 'A document' has been changed to 'Destination collection'."
    And I should see "The group of News 'Breaking news' has been changed to 'Destination collection'."
    And I should see "The group of Discussion 'Just a discussion' has been changed to 'Destination collection'."
    And I should see "The group of Custom page 'Section' has been changed to 'Destination collection'. The custom page menu link is disabled in the new group and it should be manually enabled."
    And I should see "The group of Event 'Wonderful event' has been changed to 'Destination collection'."
    And I should see "Action processing results: Move to other group (5)."

    And the "Destination collection" collection should have a community content titled "A document"
    And the "Destination collection" collection should have a community content titled "Breaking news"
    And the "Destination collection" collection should have a community content titled "Just a discussion"
    And the "Destination collection" collection should have a custom page titled "Section"
    And the "Destination collection" collection should have a community content titled "Wonderful event"
