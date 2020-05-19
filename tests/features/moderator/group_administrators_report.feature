@api
Feature: Group administrators report
  In order to have easy access to data about people with administrative roles in collections and solutions
  As a moderator
  I want to be able to see a report about group administrators and be able to download the data

  # Lower case entries in the scenario ensure case insensitive comparison.
  Scenario: Show a list of people with administrative roles in collections
    Given collections:
      | title              |
      | Large living birds |
      | bony fishes        |

    And users:
      | Username | First name  | Family name | E-mail                       |
      | najib    | Najib       | Randall     | randall@najib-industries.com |
      | melor    | Melor       | Vescovi     | melor1998@hotmail.com        |
      | kita     | Panteleimon | Kita        | pantopanto@gmail.com         |
      | major    | Major       | Jakobsen    | Major_Jakobsen@mail.dk       |
      | victor   | Victor      | Otto        | votto@fishes.co.uk           |
      | melissa  | melissa     | Kevorkian   | mkevorkian@fishes.co.uk      |

    And collection user memberships:
      | collection         | user    | roles                      | state   |
      | Large living birds | najib   | administrator              | active  |
      | bony fishes        | victor  | administrator, facilitator | active  |
      | Large living birds | melor   | facilitator                | active  |
      | bony fishes        | melissa | facilitator                | active  |
      | Large living birds | kita    | facilitator                | blocked |
      | Large living birds | major   |                            | blocked |
      | bony fishes        | melor   |                            | active  |

    And I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Group administrators and facilitators"

    Then I should see the button "Generate data"
    And I should not see the button "Download"
    And I should not see the button "Regenerate data"

    When I press "Generate data"
    And I wait for the batch process to finish
    Then I should see the success message "Data have been rebuilt."
    And I should see the button "Download"
    And I should see the button "Regenerate data"
    But I should not see the button "Generate data"

    # The data are cached for a day so refreshing the page should not need to generate the data.
    Given I reload the page
    Then I should see the button "Download"
    And I should see the button "Regenerate data"
    But I should not see the button "Generate data"

    Given I press "Download"
    Then the response should contain "\"User name\",\"User url\",\"User email\",\"Group bundle\",\"Group ID\",\"Group label\",\"Group url\",\"Is administrator\""
    And the response should contain "Victor Otto"
    And the response should contain "Melor Vescovi"
    # Only using the name partially to ensure that spaces don't affect the result.
    But the response should not contain "Najib"
    And the response should not contain "Panteleimon"
    And the response should not contain "Jakobsen"
