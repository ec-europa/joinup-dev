@api @group-f
Feature: Contact information access
  In order to see contextualized information
  As a visitor
  I don't want to see the contact information full page

  Scenario: Anonymous user contact information access
    Given the following contact:
      | email       | phil.coulson@shield.gov |
      | name        | Phil Coulson            |
      | Website URL | http://shield.gov       |
    When I am an anonymous user
    When I go to the "Phil Coulson" contact information page
    Then I should see the heading "Sign in to continue"

  Scenario: Facilitator can edit contact information
    Given the following owner:
      | name         | type    |
      | Ausy BENELUX | Company |
    Given users:
      | Username      | Roles | E-mail                      | First name | Family name |
      | Michiel Lucas |       | michiel.lucas@one-agency.be | Michiel    | Lucas       |
    And the following contact:
      | name   | info             |
      | email  | info@dataflow.be |
      | author | Michiel Lucas    |
    And collection:
      | title               | Ausy software solutions |
      | description         | A software company      |
      | logo                | logo.png                |
      | banner              | banner.jpg              |
      | owner               | Ausy BENELUX            |
      | contact information | info                    |
      | state               | validated               |
    And the following collection user memberships:
      | collection              | user          | roles       |
      | Ausy software solutions | Michiel Lucas | facilitator |
    When I am logged in as "Michiel Lucas"
    When I go to the edit form of the "Ausy software solutions" collection
    Then I should see the button "Edit" in the "Contact information inline form" region
