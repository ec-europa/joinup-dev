@api @group-d
Feature: Site alerts
  In order to announce scheduled downtime
  As a moderator
  I want to be able to display a site wide alert

  Scenario: Show a site wide alert and hide it again
    # Create a site wide alert as a moderator.
    Given I am logged in as a moderator
    When I click "Site alerts" in the "Administration toolbar" region
    Then I should see the text "There are no site alerts yet."

    When I click "Add Site Alert"
    And I fill in "Label" with "Site maintenance 18:00-20:00"
    And I select "Medium" from "Severity"
    And I enter "Between 18:00 and 20:00 the site will be temporarily unavailable due to scheduled maintenance" in the "Message" wysiwyg editor
    And I press "Save"

    # Anonymous users should see the alert.
    Given I am not logged in
    And I am on the homepage
    Then I should see the text "Between 18:00 and 20:00 the site will be temporarily unavailable due to scheduled maintenance"

    # Deactivate the alert without deleting it, so it can be used again later.
    Given I am logged in as a moderator
    When I click "Site alerts" in the "Administration toolbar" region
    And I click "Edit"
    And I uncheck "Active"
    And I press "Save"
    Then I should see the text "Not Active"

    # The alert should no longer be shown.
    Given I am not logged in
    And I am on the homepage
    Then I should not see the text "Between 18:00 and 20:00 the site will be temporarily unavailable due to scheduled maintenance"

    # Re-enable the alert, it should appear again.
    Given I am logged in as a moderator
    When I click "Site alerts" in the "Administration toolbar" region
    And I click "Edit"
    And I check "Active"
    And I press "Save"

    Given I am not logged in
    And I am on the homepage
    Then I should see the text "Between 18:00 and 20:00 the site will be temporarily unavailable due to scheduled maintenance"

    # An alert can also be removed entirely, if it does not need to be reused.
    Given I am logged in as a moderator
    When I click "Site alerts" in the "Administration toolbar" region
    And I click "Delete"
    And I press "Delete"
    Then I should see the message " The Site Alert Site maintenance 18:00-20:00 has been deleted."

    Given I am not logged in
    And I am on the homepage
    Then I should not see the text "Between 18:00 and 20:00 the site will be temporarily unavailable due to scheduled maintenance"
