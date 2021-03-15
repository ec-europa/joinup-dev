@api @terms
Feature: Date-range searching
  In order to quickly find content inside the group I am currently perusing
  As a user of Joinup
  I want to be able to launch a search limited to my current collection or solution

  @javascript
  Scenario: Users can filter with date ranges.
    Given collection:
      | title            | Liability debris |
      | logo             | logo.png         |
      | moderation       | no               |
      | policy domain    | Demography       |
      | spatial coverage | Switzerland      |
      | state            | validated        |
      | creation date    | 2018-01-11       |
      | last updated     | 2018-01-11       |
    And discussion content:
      | title      | body                           | state     | collection       | created    | publication date | changed    |
      | Room sizes | What are the ideal dimensions? | validated | Liability debris | 2019-07-15 | 2019-07-05       | 2019-11-23 |
      | Terrace?   | Want a decent sized terrace    | validated | Liability debris | 2020-07-15 | 2020-07-15       | 2020-11-23 |
    And document content:
      | title       | body               | state     | collection       | created    | publication date | changed    |
      | Ground plan | A classic design   | validated | Liability debris | 2019-09-19 | 2019-09-19       | 2019-12-17 |
      | Rock types  | Ranked by hardness | validated | Liability debris | 2020-09-19 | 2020-09-19       | 2020-12-17 |
    And event content:
      | title                        | body                              | state     | collection       | created    | publication date | changed          |
      | Opening of the winter season | Ski resorts will open in December | validated | Liability debris | 2019-04-01 | 2019-04-01       | 24-06-2019-06-24 |
      | Presenting DrillMaster X88   | Our most finely ground drill bit  | validated | Liability debris | 2020-04-01 | 2020-04-01       | 24-06-2020-06-24 |
    And news content:
      | title          | body                | state     | collection       | created    | publication date | changed    |
      | News content 1 | News content blah   | validated | Liability debris | 2020-04-17 | 2020-04-17       | 2020-06-17 |
      | News content 2 | News content blah 2 | validated | Liability debris | 2020-04-18 | 2020-04-18       | 2020-06-18 |
      | News content 3 | News content blah 3 | validated | Liability debris | 2020-04-19 | 2020-04-19       | 2020-06-19 |
      | News content 4 | News content blah 4 | validated | Liability debris | 2020-04-20 | 2020-04-20       | 2020-06-20 |
      | News content 5 | News content blah 5 | validated | Liability debris | 2020-04-21 | 2020-04-21       | 2020-06-21 |

    When I go to "/search"
    Then I should see the following tiles in the correct order:
      | Rock types                   |
      | Terrace?                     |
      | News content 5               |
      | News content 4               |
      | News content 3               |
      | News content 2               |
      | News content 1               |
      | Presenting DrillMaster X88   |
      | Ground plan                  |
      | Room sizes                   |
      | Opening of the winter season |
      | Liability debris             |

    When I fill in the "Created date minimum" date range filter with "07-15-2020"
    Then I should see the following tiles in the correct order:
      | Rock types |
      | Terrace?   |

    Then I should see the "Created date minimum" date range search filter
    And I should see the "Created date maximum" date range search filter
    And I should see the "Updated date minimum" date range search filter
    And I should see the "Updated date maximum" date range search filter

    When I fill in the "Created date minimum" date range filter with "01-01-2019"
    And I fill in the "Created date maximum" date range filter with "12-31-2019"
    Then I should see the following tiles in the correct order:
      | Ground plan                  |
      | Room sizes                   |
      | Opening of the winter season |

    When I fill in the "Created date minimum" date range filter with "01-01-2020"
    And I fill in the "Created date maximum" date range filter with "12-31-2020"
    Then I should see the following tiles in the correct order:
      | Rock types                 |
      | Terrace?                   |
      | News content 5             |
      | News content 4             |
      | News content 3             |
      | News content 2             |
      | News content 1             |
      | Presenting DrillMaster X88 |

    Scenario: Do not show the filters if there are no results found.
      Given I go to "/search"

      Then I should not see the "Created date minimum" date range search filter
      And I should not see the "Created date maximum" date range search filter
      And I should not see the "Updated date minimum" date range search filter
      And I should not see the "Updated date maximum" date range search filter
