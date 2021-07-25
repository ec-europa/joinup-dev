@api @group-a
Feature: Community TCA agreement
  In order to ensure activity by facilitators
  As a site owner
  I want users to sign the TCA agreement before proposing a community.

  Background:
    # The 'Create community' button is not shown if there is no community available.
    Given the following community:
      | title | TCA Agreement community |
      | logo  | logo.png                 |
      | state | validated                |

  Scenario: TCA agreement page is not accessible by anonymous users.
    When I am not logged in
    And I visit "/communities"
    And I click "Create community"
    Then I should see the heading "Sign in to continue"

  Scenario: Authenticated users can access the TCA agreement page.
    When I am logged in as a user with the "authenticated" role
    And I visit "/communities"
    And I click "Create community"
    Then I should see the heading "Why create a Community?"
    And I should see the text "In order to create the Community you need first check the field below and then press the Yes button to proceed."
    When I press "No thanks"
    Then the url should match "/communities"

    When I click "Create community"
    And I press "Yes"
    # No javascript test.
    Then I should see the error message "You have to agree that you will manage your community on a regular basis."
    When I check the box "I have read and accept the legal notice and I commit to manage my community on a regular basis."
    And I press "Yes"
    Then I should see the heading "Propose community"

  Scenario: TCA page contains links with additional information
    Given the following legal document version:
      | Document     | Label | Published | Acceptance label                                                                                   | Content                                                    |
      | Legal notice | 1.1   | yes       | I have read and accept the <a href="[entity_legal_document:url]">[entity_legal_document:label]</a> | The information on this site is subject to a disclaimer... |

    When I am logged in as a user with the "authenticated" role
    And I visit "/communities"
    Then I should see the warning message "You must accept this agreement before continuing."

    Given I check "I have read and accept the Legal notice"
    And I press "Submit"

    And I click "Create community"
    And I click "legal notice"
    Then I should see the heading "Legal notice"

    Given move backward one page

    And I click "eligibility criteria"
    Then I should see the heading "Eligibility criteria"

