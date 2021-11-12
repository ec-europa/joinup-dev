@api @group-f
Feature: Solution TCA agreement
  In order to ensure activity by facilitators
  As a site owner and/or a collection owner
  I want users to sign the TCA agreement before creating a solution.

  Scenario: Authenticated users can access the solution TCA agreement page.
    Given the following collection:
      | title | Agreed collection |
      | state | validated         |

    When I am logged in as a facilitator of the "Agreed collection" collection
    And I go to the "Agreed collection" collection
    And I click "Add solution" in the plus button menu

    Then I should see the heading "Terms of agreement"
    And I should see the following lines of text:
      | The eligibility criteria of Joinup's interoperability solutions have been redefined.                           |
      | In order to create the Solution you need first check the field below and then press the Yes button to proceed. |
      | I have read and accept the legal notice and I commit to manage my solution on a regular basis.                 |
