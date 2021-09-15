@api @group-a
Feature: Subscribing to community content in collections
  As a member of a collection
  I want to receive a periodic digest listing newly published content
  So that I can stay informed

  Background:
    Given the following collections:
      | title                | state     |
      | Products of Bulgaria | validated |
      | Cities of Bulgaria   | validated |
    And users:
      | Username | E-mail            | First name | Family name  | Notification frequency |
      | hristo   | hristo@example.bg | Hristo     | Draganov     | daily                  |
      | bisera   | bisera@example.bg | Bisera     | Kaloyancheva | weekly                 |
      | kalin    | kalin@primer.bg   | Kalin      | Antov        | monthly                |
    And the following collection user memberships:
      | collection           | user   | roles       |
      | Products of Bulgaria | hristo |             |
      | Products of Bulgaria | bisera |             |
      | Products of Bulgaria | kalin  |             |
      | Cities of Bulgaria   | hristo |             |
      | Cities of Bulgaria   | bisera |             |
      | Cities of Bulgaria   | kalin  | facilitator |
    And the following collection content subscriptions:
      | collection           | user   | subscriptions                        |
      | Products of Bulgaria | hristo | discussion, event, news, solution    |
      | Products of Bulgaria | bisera | discussion, document, news           |
      | Products of Bulgaria | kalin  | document, event                      |
      | Cities of Bulgaria   | hristo | document, event, solution            |
      | Cities of Bulgaria   | bisera | discussion, event, news              |
      | Cities of Bulgaria   | kalin  | discussion, document, news, solution |
    And all message digests have been delivered

  Scenario: Receive a digest of content that is published in my collections
    Given discussion content:
      | title      | body                      | collection           | state     | author |
      | Duck liver | Rich buttery and delicate | Products of Bulgaria | validated | hristo |
      | Sofia      | Grows without aging       | Cities of Bulgaria   | validated | kalin  |
      | Ruse       | Little Vienna             | Cities of Bulgaria   | proposed  | kalin  |
    And document content:
      | title           | body                   | collection           | state     | author |
      | Canned cherries | Sour cherries for pies | Products of Bulgaria | validated | bisera |
      | Plovdiv         | Seven hills            | Cities of Bulgaria   | validated | hristo |
    And event content:
      | title           | body           | collection           | state     | author | start date          | end date            |
      | Sunflower seeds | A tasty snack  | Products of Bulgaria | validated | bisera | 2019-11-28T11:12:13 | 2019-11-28T11:12:13 |
      | Varna           | Summer capital | Cities of Bulgaria   | draft     | kalin  | 2019-12-05T12:00:00 | 2019-12-15T12:00:00 |
      | Stara Zagora    | Historic       | Cities of Bulgaria   | validated | hristo | 2020-01-18T18:30:00 | 2020-01-19T00:00:00 |
    And news content:
      | title    | body                        | collection           | state     | author |
      | Rose oil | A widely used essential oil | Products of Bulgaria | validated | bisera |
      | Burgas   | City of dreams              | Cities of Bulgaria   | validated | hristo |
    And solutions:
      | title          | description                      | collection           | state     | author |
      | Double seaming | The rolls roll around the chuck  | Products of Bulgaria | proposed  | kalin  |
      | Belt conveyors | As troughed belts gently slope   | Products of Bulgaria | validated | bisera |
      | New urbanism   | Context-appropriate architecture | Cities of Bulgaria   | validated | hristo |

    Then the daily group content subscription digest for hristo should match the following messages:
      | Belt conveyors  |
      | New urbanism    |
      | Duck liver      |
      | Sunflower seeds |
      | Rose oil        |
      | Plovdiv         |
      | Stara Zagora    |
    And the weekly group content subscription digest for bisera should match the following message:
      | Duck liver      |
      | Canned cherries |
      | Rose oil        |
      | Sofia           |
      | Stara Zagora    |
      | Burgas          |
    And the monthly group content subscription digest for kalin should match the following message:
      | Canned cherries |
      | Sunflower seeds |
      | Sofia           |
      | Plovdiv         |
      | Burgas          |
      | New urbanism    |

    # Check that only the user's chosen frequency is digested.
    But the weekly digest for hristo should not contain any messages
    And the monthly digest for hristo should not contain any messages
    And the daily digest for bisera should not contain any messages
    And the monthly digest for bisera should not contain any messages
    And the daily digest for kalin should not contain any messages
    And the weekly digest for kalin should not contain any messages

    # The digest should not include news about content that is not published.
    And the daily group content subscription digest for hristo should not contain the following messages:
      | Double seaming |
      | Varna          |
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
    Then the group content subscription digest sent to hristo contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Plovdiv              |
      | Stara Zagora         |
      | Products of Bulgaria |
      | Belt conveyors       |
      | Duck liver           |
      | Rose oil             |
      | Sunflower seeds      |
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
    And the content subscription digest sent to bisera should have the subject "Joinup: Weekly digest message"

    And the group content subscription digest sent to kalin contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Burgas               |
      | New urbanism         |
      | Plovdiv              |
      | Sofia                |
      | Products of Bulgaria |
      | Canned cherries      |
      | Sunflower seeds      |
    And the content subscription digest sent to kalin should have the subject "Joinup: Monthly digest message"

    # Clean out the message queue for the next test.
    And I mark all emails are read

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

    # Test publication of a solution.
    When the workflow state of the "Double seaming" solution is changed to "validated"
    Then the daily group content subscription digest for hristo should include the following messages:
      | Double seaming |

    # Check that the messages are formatted correctly.
    Given all message digests have been delivered
    Then the group content subscription digest sent to hristo contains the following sections:
      | title          |
      | Double seaming |
