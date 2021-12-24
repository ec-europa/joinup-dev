@api
Feature: Publish draft collection

  # This test is solely intended to measure the time it takes to publish a
  # collection which is in draft state. It is a subset of another test so it has
  # no value on its own and should not be merged into the main branch.
  # https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4434
  Scenario: Publish a draft collection
    Given the following owner:
      | name           |
      | Simon Sandoval |
    And the following contact:
      | name  | Francis             |
      | email | Francis@example.com |
    And users:
      | Username        | Roles     |
      | Lena Richardson | moderator |
    And the following collections:
      | title     | description | logo     | banner     | owner          | contact information | policy domain | state |
      | Deep Past | Azure ship  | logo.png | banner.jpg | Simon Sandoval | Francis             | Licensing     | draft |

    Given I am logged in as "Lena Richardson"
    And I go to the "Deep Past" collection
    And I click "Edit"
    Then the current workflow state should be "Draft"
    When I press "Publish"
    Then I should see the heading "Deep Past"
