@api @group-a
Feature: Subscribing to community content in solutions
  As a member of a solution
  I want to receive a periodic digest listing newly published content
  So that I can stay informed

  Background:
    Given the following collections:
      | title    | state     |
      | Bulgaria | validated |
    Given the following solutions:
      | title                | state     | collection |
      | Products of Bulgaria | validated | Bulgaria   |
      | Cities of Bulgaria   | validated | Bulgaria   |
    And users:
      | Username | E-mail            | First name | Family name  | Notification frequency |
      | hristo   | hristo@example.bg | Hristo     | Draganov     | daily                  |
      | bisera   | bisera@example.bg | Bisera     | Kaloyancheva | weekly                 |
      | kalin    | kalin@primer.bg   | Kalin      | Antov        | monthly                |
    And the following solution user memberships:
      | solution             | user   | roles       |
      | Products of Bulgaria | hristo |             |
      | Products of Bulgaria | bisera |             |
      | Products of Bulgaria | kalin  |             |
      | Cities of Bulgaria   | hristo |             |
      | Cities of Bulgaria   | bisera |             |
      | Cities of Bulgaria   | kalin  | facilitator |
    And the following solution content subscriptions:
      | solution             | user   | subscriptions                          |
      | Products of Bulgaria | hristo | discussion, event, news, distribution  |
      | Products of Bulgaria | bisera | discussion, document, news, release    |
      | Products of Bulgaria | kalin  | document, event, distribution, release |
      | Cities of Bulgaria   | hristo | document, event                        |
      | Cities of Bulgaria   | bisera | discussion, event, news                |
      | Cities of Bulgaria   | kalin  | discussion, document, news             |
    And all message digests have been delivered
    And the mail collector cache is empty

  Scenario: Receive a digest of content that is published in my solutions
    Given discussion content:
      | title      | body                      | solution             | state     | author |
      | Duck liver | Rich buttery and delicate | Products of Bulgaria | validated | hristo |
      | Sofia      | Grows without aging       | Cities of Bulgaria   | validated | kalin  |
      | Ruse       | Little Vienna             | Cities of Bulgaria   | proposed  | kalin  |
    And document content:
      | title           | body                   | solution             | state     | author |
      | Canned cherries | Sour cherries for pies | Products of Bulgaria | validated | bisera |
      | Plovdiv         | Seven hills            | Cities of Bulgaria   | validated | hristo |
    And event content:
      | title           | body           | solution             | state     | author | start date          | end date            |
      | Sunflower seeds | A tasty snack  | Products of Bulgaria | validated | bisera | 2019-11-28T11:12:13 | 2019-11-28T11:12:13 |
      | Varna           | Summer capital | Cities of Bulgaria   | draft     | kalin  | 2019-12-05T12:00:00 | 2019-12-15T12:00:00 |
      | Stara Zagora    | Historic       | Cities of Bulgaria   | validated | hristo | 2020-01-18T18:30:00 | 2020-01-19T00:00:00 |
    And news content:
      | title    | body                        | solution             | state     | author |
      | Rose oil | A widely used essential oil | Products of Bulgaria | validated | bisera |
      | Burgas   | City of dreams              | Cities of Bulgaria   | validated | hristo |
    And releases:
      | title       | release number | release notes   | is version of        | state     |
      | Spring 2021 | 2021/03        | Blooming pears. | Products of Bulgaria | validated |
    And distributions:
      | title                 | description    | parent               |
      # A distribution linked directly to the solution.
      | Full list of products | There are many | Products of Bulgaria |
      # A distribution linked to a release.
      | Spring discounts      | Get them now   | Spring 2021          |

    Then the daily group content subscription digest for hristo should match the following messages:
      | Duck liver            |
      | Sunflower seeds       |
      | Rose oil              |
      | Plovdiv               |
      | Stara Zagora          |
      | Full list of products |
      | Spring discounts      |
    And the weekly group content subscription digest for bisera should match the following message:
      | Duck liver      |
      | Canned cherries |
      | Rose oil        |
      | Sofia           |
      | Stara Zagora    |
      | Burgas          |
      | Spring 2021     |
    And the monthly group content subscription digest for kalin should match the following message:
      | Canned cherries       |
      | Sunflower seeds       |
      | Sofia                 |
      | Plovdiv               |
      | Burgas                |
      | Spring 2021           |
      | Full list of products |
      | Spring discounts      |

    # Check that only the user's chosen frequency is digested.
    But the weekly digest for hristo should not contain any messages
    And the monthly digest for hristo should not contain any messages
    And the daily digest for bisera should not contain any messages
    And the monthly digest for bisera should not contain any messages
    And the daily digest for kalin should not contain any messages
    And the weekly digest for kalin should not contain any messages

    # The digest should not include news about content that is not published.
    And the daily group content subscription digest for hristo should not contain the following messages:
      | Varna |
    And the weekly group content subscription digest for bisera should not contain the following message:
      | Ruse  |
      | Varna |
    And the monthly group content subscription digest for kalin should not contain the following message:
      | Ruse |

    # Publish an existing unpublished community content. It should be included
    # in the next digest.
    When the workflow state of the "Ruse" content is changed to "validated"

    Then the weekly group content subscription digest for bisera should include the following message:
      | Ruse |
    And the monthly group content subscription digest for kalin should include the following message:
      | Ruse |

    # Check that the messages are formatted correctly.
    Given all message digests have been delivered
    And the group content subscription digest sent to hristo contains the following sections:
      | title                 |
      | Cities of Bulgaria    |
      | Plovdiv               |
      | Stara Zagora          |
      | Products of Bulgaria  |
      | Duck liver            |
      | Full list of products |
      | Rose oil              |
      | Spring discounts      |
      | Sunflower seeds       |
    And the content subscription digest sent to hristo should have the subject "Joinup: Daily digest message"

    And the group content subscription digest sent to bisera contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Burgas               |
      | Sofia                |
      | Stara Zagora         |
      | Products of Bulgaria |
      | Canned cherries      |
      | Rose oil             |
      | Spring 2021          |
    And the content subscription digest sent to bisera should have the subject "Joinup: Weekly digest message"

    And the group content subscription digest sent to kalin contains the following sections:
      | title                 |
      | Cities of Bulgaria    |
      | Burgas                |
      | Plovdiv               |
      | Sofia                 |
      | Products of Bulgaria  |
      | Canned cherries       |
      | Full list of products |
      | Spring 2021           |
      | Spring discounts      |
      | Sunflower seeds       |
    And the content subscription digest sent to kalin should have the subject "Joinup: Monthly digest message"

    # Clean out the message queue for the next test.
    And the mail collector cache is empty

    # Check that if community content is published a second time it is not
    # included in the next digest.
    When the workflow state of the "Ruse" content is changed to "draft"
    Then the weekly group content subscription digest for bisera should not contain the following message:
      | Ruse |
    And the monthly group content subscription digest for kalin should not contain the following message:
      | Ruse |
    When the workflow state of the "Ruse" content is changed to "validated"
    Then the weekly group content subscription digest for bisera should not contain the following message:
      | Ruse |
    And the monthly group content subscription digest for kalin should not contain the following message:
      | Ruse |
