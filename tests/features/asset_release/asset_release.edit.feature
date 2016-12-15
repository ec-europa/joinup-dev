@api
Feature: "Edit" visibility options.
  In order to manage releases
  As a solution facilitator
  I need to be able to edit "Release" rdf entities through UI.

  Background:
    Given the following owner:
      | name           |
      | Awesome person |
    And the following contact:
      | name        | Awesome contact            |
      | email       | awesomecontact@example.com |
      | Website URL | http://example.com         |
    And the following solution:
      | title               | My awesome solution abc |
      | description         | My awesome solution     |
      | documentation       | text.pdf                |
      | owner               | Awesome person          |
      | contact information | Awesome contact         |
      | state               | validated               |
    And the following release:
      | title               | My awesome solution abc v1 |
      | description         | A sample release           |
      | documentation       | text.pdf                   |
      | release number      | 1                          |
      | release notes       | Changed release            |
      | is version of       | My awesome solution abc    |
      | owner               | Awesome person             |
      | contact information | Awesome contact            |
      | state               | validated                  |

  Scenario: "Edit" button should only be shown to solution facilitators and moderators.
    When I am logged in as a "facilitator" of the "My awesome solution abc" solution
    And I go to the homepage of the "My awesome solution abc v1" release
    Then I should see the link "Edit" in the "Entity actions" region

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "My awesome solution abc v1" release
    Then I should not see the link "Edit" in the "Entity actions" region

    When I am an anonymous user
    And I go to the homepage of the "My awesome solution abc v1" release
    Then I should not see the link "Edit" in the "Entity actions" region
