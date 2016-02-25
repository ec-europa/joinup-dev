@api
Feature: Proposing a collection
  In order to create a new collection on Joinup
  As the product owner of a collection of software solutions
  I need to be able to propose a collection for inclusion on Joinup

  # Todo: It still needs to be decided on which pages the "Add collection"
  # button will be shown. It might be removed from the homepage in the future.
  # Ref. https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2298

  # An anonymous user should be shown the option to add a collection, so that
  # the user will be aware that collections can be added by the public, even
  # though you need to log in to do so.
  Scenario: Anonymous user needs to log in before creating a collection
    Given users:
    | name          | pass  |
    | Cecil Clapman | claps |
    Given I am an anonymous user
    When I am on the homepage
    And I click "Add collection"
    Then I should see the heading "Access denied"
    When I fill in the following:
    | Username | Cecil Clapman |
    | Password | claps         |
    And I press "Log in"
    Then I should see the heading "Propose a collection"

  Scenario: Propose a collection
    Given I am logged in as a user with the "authenticated" role
    When I am on the homepage
    And I click "Add collection"
    Then I should see the heading "Propose a collection"

