@api @terms
Feature: Document moderation
  In order to manage documents
  As a user of the website
  I need to be able to transit the documents from one state to another.

  Background:
    Given users:
      | Username        |
      | Crab y Patties  |
      | Gretchen Greene |
      | Kirk Collier    |
    And the following owner:
      | name          |
      | thisisanowner |
    And the following collection:
      | title             | The Naked Ashes                 |
      | description       | The wolverine is a Marvel hero. |
      | logo              | logo.png                        |
      | banner            | banner.jpg                      |
      | elibrary creation | registered users                |
      | moderation        | no                              |
      | state             | validated                       |
      | owner             | thisisanowner                   |
      | policy domain     | E-inclusion                     |
    And the following collection user membership:
      | collection      | user            | roles       |
      | The Naked Ashes | Gretchen Greene | member      |
      | The Naked Ashes | Kirk Collier    | facilitator |

  @javascript
  Scenario: Available transitions change per eLibrary and moderation settings.
    # For post-moderated collections with eLibrary set to allow all users to
    # create content, authenticated users that are not members can create
    # documents.
    When I am logged in as "Crab y Patties"
    And go to the homepage of the "The Naked Ashes" collection
    And I click "Add document" in the plus button menu
    # Post moderated collections allow publishing content directly.
    And I should see the button "Publish"

    # Edit the collection and set it as moderated.
    When I am logged in as a moderator
    And I go to the homepage of the "The Naked Ashes" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    And I check the box "Moderated"
    Then I press "Publish"
    And I should see the heading "The Naked Ashes"

    # The parent group is now pre-moderated: authenticated non-member users
    # should still be able to create documents but not to publish them.
    When I am logged in as "Crab y Patties"
    And I go to the homepage of the "The Naked Ashes" collection
    And I click "Add document" in the plus button menu
    Then I should not see the button "Publish"
    But I should see the button "Save as draft"
    And I should see the button "Propose"

    # Edit the collection and set it to allow only members to create new
    # content.
    When I am logged in as a moderator
    And I go to the homepage of the "The Naked Ashes" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    And I check "Closed collection"
    And I wait for AJAX to finish
    And I select "Only members can create new content." from "eLibrary creation"
    And I press "Publish"
    # I should now have the possibility to add documents.
    When I open the plus button menu
    Then I should see the link "Add document"

    # Non-members should not be able to create documents anymore.
    When I am logged in as "Crab y Patties"
    And I go to the homepage of the "The Naked Ashes" collection
    Then the plus button menu should be empty

  Scenario: Transit documents from one state to another.
    When I am logged in as "Gretchen Greene"
    And I go to the homepage of the "The Naked Ashes" collection
    And I click "Add document" in the plus button menu
    When I fill in the following:
      | Title       | An amazing document                      |
      | Short title | Amazing document                         |
    And I enter "This is going to be an amazing document." in the "Description" wysiwyg editor
    And I select "Document" from "Type"
    Then I upload the file "test.zip" to "Upload a new file or enter a URL"
    And I press "Save as draft"
    Then I should see the success message "Document An amazing document has been created"

    # Publish the content.
    When I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Draft"
    When I fill in "Title" with "A not so amazing document"
    And I press "Publish"
    Then I should see the heading "A not so amazing document"

    # Request modification as facilitator.
    When I am logged in as "Kirk Collier"
    And I go to the homepage of the "The Naked Ashes" collection
    And I click "A not so amazing document"
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Published"
    And the following fields should be present "Motivation"
    And I should see the button "Request changes"

    # Implement changes as owner of the document.
    Given I fill in "Motivation" with "Request some regression changes"
    And I press "Request changes"
    When I am logged in as "Gretchen Greene"
    And I go to the homepage of the "The Naked Ashes" collection
    And I click "A not so amazing document"
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Proposed"
    When I fill in "Title" with "The document is amazing"
    And I press "Update"
    Then I should see the heading "A not so amazing document"

    # Approve changes as facilitator.
    When I am logged in as "Kirk Collier"
    And I go to the homepage of the "The Naked Ashes" collection
    And I click "A not so amazing document"
    And I click "Edit" in the "Entity actions" region
    Then the current workflow state should be "Proposed"
    And I should see the button "Publish"
    When I press "Publish"
    Then I should see the heading "The document is amazing"
