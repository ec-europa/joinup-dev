@api @group-g
Feature: Subscribers report
  In order to get a view on how many members are subscribed to our content
  As a facilitator
  I want to have access to a subscribers report

  Scenario: View a report on the number of subscribers
    Given users:
      | Username | Roles | First name | Family name |
      | busta    |       | Busta      | Dog         |
      | method   |       | Method     | Funk        |
      | biz      |       | Biz        | 3000        |
      | gangsta  |       | Gangsta    | Freak       |
      | papa     |       | Papa       | E           |
      | dope     |       | Dope       | Ice         |
      | young    |       | Young      | G           |
      | lil      |       | Lil        | Mama        |
    And collections:
      | title             | state     |
      | Marine ecosystems | validated |
      | Plant science     | validated |
    And solutions:
      | title                 | state     | collection        |
      | Seagrass meadows      | validated | Marine ecosystems |
      | Intertidal seagrasses | validated | Plant science     |
    And collection user memberships:
      | collection        | user    | roles       |
      | Marine ecosystems | busta   | facilitator |
      | Marine ecosystems | method  |             |
      | Marine ecosystems | biz     |             |
      | Marine ecosystems | gangsta |             |
      | Marine ecosystems | papa    |             |
      | Marine ecosystems | dope    |             |
      | Plant science     | young   | facilitator |
      | Plant science     | lil     |             |
      | Plant science     | busta   |             |
      | Plant science     | method  |             |
      | Plant science     | biz     |             |
      | Plant science     | gangsta |             |
    And solution user memberships:
      | solution              | user    | roles       |
      | Seagrass meadows      | papa    | facilitator |
      | Seagrass meadows      | dope    |             |
      | Seagrass meadows      | young   |             |
      | Seagrass meadows      | lil     |             |
      | Seagrass meadows      | busta   |             |
      | Seagrass meadows      | method  |             |
      | Intertidal seagrasses | biz     | facilitator |
      | Intertidal seagrasses | gangsta |             |
      | Intertidal seagrasses | papa    |             |
      | Intertidal seagrasses | dope    |             |
      | Intertidal seagrasses | young   |             |
      | Intertidal seagrasses | lil     |             |
    And collection content subscriptions:
      | collection        | user    | subscriptions                   |
      | Marine ecosystems | busta   | document, news, solution        |
      | Marine ecosystems | method  | document, solution              |
      | Marine ecosystems | biz     | discussion, event               |
      | Marine ecosystems | gangsta | discussion, document, event     |
      | Marine ecosystems | papa    | document, solution              |
      | Marine ecosystems | dope    | document, event, news, solution |
      | Plant science     | young   | discussion, document, event     |
      | Plant science     | lil     | document, event                 |
      | Plant science     | busta   | discussion, document, event     |
      | Plant science     | method  | document, event                 |
      | Plant science     | biz     | discussion, news                |
      | Plant science     | gangsta | event, news                     |
    And solution content subscriptions:
      | solution              | user    | subscriptions                     |
      | Seagrass meadows      | papa    | document, event                   |
      | Seagrass meadows      | dope    | discussion, document, event       |
      | Seagrass meadows      | young   | discussion, event, news           |
      | Seagrass meadows      | lil     | document, event                   |
      | Seagrass meadows      | busta   | discussion, document, event       |
      | Seagrass meadows      | method  | discussion, document              |
      | Intertidal seagrasses | biz     | discussion, news                  |
      | Intertidal seagrasses | gangsta | discussion, event                 |
      | Intertidal seagrasses | papa    | event, news                       |
      | Intertidal seagrasses | dope    | document, news                    |
      | Intertidal seagrasses | young   | event                             |
      | Intertidal seagrasses | lil     | discussion, document, event, news |

    # Anonymous users cannot access the reports.
    Given I am not logged in
    When I go to the global subscribers report
    Then I should see the text "Sign in to continue"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Sign in to continue"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Sign in to continue"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Sign in to continue"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Sign in to continue"

    # Non-members cannot access the reports.
    Given I am logged in as an "authenticated user"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    # Moderators can access all reports.
    Given I am logged in as a "moderator"
    When I go to the global subscribers report
    Then I should see the heading "Subscribers report"
    And the "subscribers report" table should contain:
      | Group                 | Type       | Subscribers | Solution | Discussion | Document | Event | News |
      | Intertidal seagrasses | solution   | 6           | 0        | 3          | 2        | 4     | 4    |
      | Marine ecosystems     | collection | 6           | 4        | 2          | 5        | 3     | 2    |
      | Plant science         | collection | 6           | 0        | 3          | 4        | 5     | 2    |
      | Seagrass meadows      | solution   | 6           | 0        | 4          | 5        | 5     | 1    |
    When I go to the subscribers report for "Marine ecosystems"
    # Check that the collection header is shown.
    Then I should see the heading "Marine ecosystems" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 4 |
      | Discussion  | 2 |
      | Document    | 5 |
      | Event       | 3 |
      | News        | 2 |
    When I go to the subscribers report for "Plant science"
    Then I should see the heading "Plant science" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 3 |
      | Document    | 4 |
      | Event       | 5 |
      | News        | 2 |
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the heading "Seagrass meadows" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 4 |
      | Document    | 5 |
      | Event       | 5 |
      | News        | 1 |
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the heading "Intertidal seagrasses" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 3 |
      | Document    | 2 |
      | Event       | 4 |
      | News        | 4 |

    Given I am logged in as "busta"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the heading "Marine ecosystems" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 4 |
      | Discussion  | 2 |
      | Document    | 5 |
      | Event       | 3 |
      | News        | 2 |
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "method"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "biz"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the heading "Intertidal seagrasses" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 3 |
      | Document    | 2 |
      | Event       | 4 |
      | News        | 4 |

    Given I am logged in as "gangsta"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "papa"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the heading "Seagrass meadows" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 4 |
      | Document    | 5 |
      | Event       | 5 |
      | News        | 1 |
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "dope"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "young"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the heading "Plant science" in the "Header" region
    And I should see the heading "Subscribers report" in the "Page title"
    And the "subscribers report" table should contain:
      | Subscribers | 6 |
      | Solution    | 0 |
      | Discussion  | 3 |
      | Document    | 4 |
      | Event       | 5 |
      | News        | 2 |
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"

    Given I am logged in as "lil"
    When I go to the global subscribers report
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Marine ecosystems"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Plant science"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Seagrass meadows"
    Then I should see the text "Access denied"
    When I go to the subscribers report for "Intertidal seagrasses"
    Then I should see the text "Access denied"
