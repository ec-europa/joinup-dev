@api @javascript @terms @clearStaticCache @group-c
Feature: Tests the collection last update time.

  Scenario: As a user visiting a collection, I want to see the collection last
    update time as the maximum changed time of the collection itself, its
    affiliate solutions, community content and custom pages.

    Given users:
      | Username    | Roles     |
      | The Cleaner | moderator |
    And  the following contact:
      | email | foo@bar.com           |
      | name  | Your Cleaning Company |
    And the following owner:
      | name | type                  |
      | Sean | Private Individual(s) |
    And the following collection:
      | title             | Household Wizard |
      | state             | validated        |
      | modification date | 2011-05-06T21:56 |
      | creation date     | 2001-01-03       |
      | author            | The Cleaner      |
    And the following solutions:
      | title           | collection       | state     | modification date | creation date | author      | contact information   | owner | description                    |
      | Roof Hole Cover | Household Wizard | validated | 2010-07-05T23:03  | 2001-01-01    | The Cleaner | Your Cleaning Company | Sean  | Keep your roof in a good shape |
      | Mosquito Killer | Household Wizard | proposed  | 2017-05-03T11:45  | 2001-01-02    | The Cleaner | Your Cleaning Company | Sean  | Quiet nights                   |
    And I am logged in as "The Cleaner"

    Given I go to the homepage of the "Household Wizard" collection
    # The newest is 'Mosquito Killer' solution but because is not validated, it
    # doesn't count. The winner is 'Household Wizard' collection because is
    # newer than 'Roof Hole Cover' solution.
    Then the response should contain "2011-05-06T21:56"

    Given document content:
      | title           | document type | state     | changed          | created    | collection       | body               |
      | Get Rid of Rats | Document      | validated | 2012-01-17T07:38 | 2001-01-04 | Household Wizard | Rats everywhere... |

    And I reload the page
    # The document is the newest thus will give the collection updated time.
    Then the response should contain "2012-01-17T07:38"

    Given discussion content:
      | title           | state     | changed          | created    | collection       |
      | Household Forum | validated | 2013-04-19T15:18 | 2001-01-05 | Household Wizard |

    And I reload the page
    # The discussion is the newest thus will give the collection updated time.
    Then the response should contain "2013-04-19T15:18"

    Given event content:
      | title        | state     | changed          | created    | collection       |
      | Cleaning Day | validated | 2014-06-06T22:46 | 2001-01-06 | Household Wizard |

    And I reload the page
    # The event is the newest thus will give the collection updated time.
    Then the response should contain "2014-06-06T22:46"

    Given news content:
      | title                 | state | changed          | created    | collection       |
      | The New Grass Trimmer | draft | 2015-11-08T01:05 | 2001-01-07 | Household Wizard |

    And I reload the page
    # The event is the newest but is not validated, so it doesn't influence the
    # collection updated time.
    Then the response should not contain "2015-11-08T01:05"
    But the response should contain "2014-06-06T22:46"

    Given custom_page content:
      | title          | changed          | created    | collection       |
      | The Kids Space | 2016-05-06T05:29 | 2001-01-07 | Household Wizard |

    And I reload the page
    # The custom page is the newest thus will give the collection updated time.
    Then the response should contain "2016-05-06T05:29"

    When I go to the edit form of the "Get Rid of Rats" document
    And I press "Update"
    # The updated time has changed to the current time but we cannot catch the
    # time we ran the update.
    Then the response should not contain "2016-05-06T05:29"
    And I should see "few seconds ago"

    # Let's see how the timeago jquery widget changes.
    # Note for QA: If the reviewer decide that we cannot afford to sleep 1 min
    # while running the tests, we can remove this part.
    Given I wait until the page contains the text "about a minute ago"
    Then I should see "about a minute ago"

    # Delete a community content node.
    Given I go to the content page of the type document with the title "Get Rid of Rats"
    # Open the contextual menu that contains the local primary tasks.
    And I open the header local tasks menu
    Given I click "Delete"
    When I press "Delete"
    And I go to the homepage of the "Household Wizard" collection
    # The deletion of community content node changes the last updated time.
    Then I should see "few seconds ago"

    Given I wait until the page contains the text "about a minute ago"
    Then I should see "about a minute ago"

    # Editing the solution changes the last updated time.
    Given I go to the edit form of the "Roof Hole Cover" solution
    And I select "Public Policy Cycle" from "Solution type"
    And I select "Demography" from "Topic"
    And I press "Publish"

    When I go to the homepage of the "Household Wizard" collection
    Then I should see "few seconds ago"

    Given I wait until the page contains the text "about a minute ago"
    Then I should see "about a minute ago"

    # Deleting the solution changes the last updated time.
    Given I go to the edit form of the "Roof Hole Cover" solution
    And I click "Delete"
    When I press "Delete"
    And I go to the homepage of the "Household Wizard" collection
    # The deletion of community content node refreshes the last updated time.
    Then I should see "few seconds ago"
