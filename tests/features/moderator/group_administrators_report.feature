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
      | Username | First name  | Family name | E-mail                       |
      | najib    | Najib       | Randall     | randall@najib-industries.com |
      | melor    | Melor       | Vescovi     | melor1998@hotmail.com        |
      | kita     | Panteleimon | Kita        | pantopanto@gmail.com         |
      | major    | Major       | Jakobsen    | Major_Jakobsen@mail.dk       |
      | victor   | Victor      | Otto        | votto@fishes.co.uk           |
      | melissa  | Melissa     | Kevorkian   | mkevorkian@fishes.co.uk      |

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
      | Collection         | User name         | E-mail                       | Role          |
      | Large living birds | Najib Randall     | randall@najib-industries.com | administrator |
      | Large living birds | Melor Vescovi     | melor1998@hotmail.com        | facilitator   |
      | Large living birds | Panteleimon Kita  | pantopanto@gmail.com         | facilitator   |
      | Bony fishes        | Victor Otto       | votto@fishes.co.uk           | administrator |
      | Bony fishes        | Victor Otto       | votto@fishes.co.uk           | facilitator   |
      | Bony fishes        | Melissa Kevorkian | mkevorkian@fishes.co.uk      | facilitator   |

    And the "collection administrator report" table should not contain the following columns:
      | Collection         | User name      |
      | Large living birds | Major Jacobsen |
      | Bony fishes        | Melor Vescovi  |
