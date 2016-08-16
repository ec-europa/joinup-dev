@api
Feature: Add asset distribution through the UI
  In order to manage distributions
  As a moderator
  I need to be able to add "Asset distribution" RDF entities through the UI.

  Background:
    Given the following solution:
      | title       | Solution random x name           |
      | description | Some reusable random description |
    And the following collection:
      | title      | Asset Distribution Test |
      | logo       | logo.png                |
      | affiliates | Solution random x name  |
    And the following asset release:
      | title         | Asset release random name        |
      | description   | Some reusable random description |
      | is version of | Solution random x name           |

    Scenario: "Add distribution" button should not be shown to unprivileged users.
      When I am logged in as a "facilitator" of the "Solution random x name" solution
      And I go to the homepage of the "Asset release random name" asset release
      # Click the + button.
      Then I click "Add"
      Then I should see the link "Add distribution"

      When I am logged in as an "authenticated user"
      And I go to the homepage of the "Asset release random name" asset release
      Then I should not see the link "Add distribution"

      When I am an anonymous user
      And I go to the homepage of the "Asset release random name" asset release
      Then I should not see the link "Add distribution"

    Scenario: Add distribution as a facilitator.
      Given the following licence:
        | title       | WTFPL                                    |
        | description | The WTFPL is a rather permissive licence |
      When I am logged in as a "facilitator" of the "Solution random x name" solution
      When I go to the homepage of the "Asset release random name" asset release
      And I click "Add distribution"
      Then I should see the heading "Add Asset distribution"
      And the following fields should be present "Title, Description, License, Format, Representation technique, GITB compliant"
      # Field labels are implemented not consistently, so we are
      # forced to check for the widget heading.
      # @todo to be handled in ISAICP-2655
      And I should see the text "Access URL"
      And I should see the text "Distribution file"
      When I fill in "Title" with "Custom title of asset distribution"
      And I attach the file "test.zip" to "Add a new file"
      And I fill in "License" with "WTFPL"
      And I press "Save"
      Then I should have 1 asset distribution
      # Check if the asset distribution is accessible as an anonymous user
      When I go to the homepage of the "Asset release random name" asset release
      Then I should see the text "Distribution"
      And I should see the link "Custom title of asset distribution"
      When I click "Custom title of asset distribution"
      Then I should see the heading "Custom title of asset distribution"
      # Clean up the asset distribution that was created through the UI.
      Then I delete the "Custom title of asset distribution" asset distribution
