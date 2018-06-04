@api
Feature: Group administrators report
  In order to have easy access to data about people with administrative roles in collections and solutions
  As a moderator
  I want to be able to see a report about group administrators and be able to download the data

  Scenario: Show a list of people with administrative roles in collections
    Given collections:
      | title              |
      | Large living birds |
      | Bony fishes        |

    And users:
      | Username | First name  | Family name |
      | najib    | Najib       | Randall     |
      | melor    | Melor       | Vescovi     |
      | kita     | Panteleimon | Kita        |
      | major    | Major       | Jakobsen    |
      | victor   | Victor      | Otto        |
      | melissa  | Melissa     | Kevorkian   |

    And collection user memberships:
      | collection         | user    | roles                      | state   |
      | Large living birds | najib   | administrator              | active  |
      | Large living birds | melor   | facilitator                | active  |
      | Large living birds | kita    | facilitator                | blocked |
      | Large living birds | major   |                            | blocked |
      | Bony fishes        | victor  | administrator, facilitator | active  |
      | Bony fishes        | melissa | facilitator                | active  |
      | Bony fishes        | melor   |                            | active  |

    And I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Collection administrators"

    Then the "collection administrator report" table should contain the following columns:
      | Collection         | User name         | Role          |
      | Large living birds | Najib Randall     | administrator |
      | Large living birds | Melor Vescovi     | facilitator   |
      | Large living birds | Panteleimon Kita  | facilitator   |
      | Bony fishes        | Victor Otto       | administrator |
      | Bony fishes        | Victor Otto       | facilitator   |
      | Bony fishes        | Melissa Kevorkian | facilitator   |

    And the "collection administrator report" table should not contain the following columns:
      | Collection         | User name      |
      | Large living birds | Major Jacobsen |
      | Bony fishes        | Melor Vescovi  |
