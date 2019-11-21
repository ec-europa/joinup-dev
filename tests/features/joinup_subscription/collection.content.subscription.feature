@api
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
      | collection           | user   | subscriptions              |
      | Products of Bulgaria | hristo | discussion, event, news    |
      | Products of Bulgaria | bisera | discussion, document, news |
      | Products of Bulgaria | kalin  | document, event            |
      | Cities of Bulgaria   | hristo | document, event            |
      | Cities of Bulgaria   | bisera | discussion, event, news    |
      | Cities of Bulgaria   | kalin  | discussion, document, news |
    And all message digests have been delivered
    And the mail collector cache is empty

  @email
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
      | title           | body           | collection           | state     | author |
      | Sunflower seeds | A tasty snack  | Products of Bulgaria | validated | bisera |
      | Varna           | Summer capital | Cities of Bulgaria   | draft     | kalin  |
      | Stara Zagora    | Historic       | Cities of Bulgaria   | validated | hristo |
    And news content:
      | title    | body                        | collection           | state     | author |
      | Rose oil | A widely used essential oil | Products of Bulgaria | validated | bisera |
      | Burgas   | City of dreams              | Cities of Bulgaria   | validated | hristo |

    Then the daily digest for hristo should contain the following message:
      | mail_subject | Duck liver                |
      | mail_body    | Rich buttery and delicate |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Sunflower seeds |
      | mail_body    | A tasty snack   |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Rose oil                    |
      | mail_body    | A widely used essential oil |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Plovdiv     |
      | mail_body    | Seven hills |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Stara Zagora |
      | mail_body    | Historic     |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Duck liver                |
      | mail_body    | Rich buttery and delicate |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Canned cherries        |
      | mail_body    | Sour cherries for pies |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Rose oil                    |
      | mail_body    | A widely used essential oil |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Sofia               |
      | mail_body    | Grows without aging |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Stara Zagora |
      | mail_body    | Historic     |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Burgas         |
      | mail_body    | City of dreams |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Canned cherries        |
      | mail_body    | Sour cherries for pies |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Sunflower seeds |
      | mail_body    | A tasty snack   |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Sofia               |
      | mail_body    | Grows without aging |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Plovdiv     |
      | mail_body    | Seven hills |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Burgas         |
      | mail_body    | City of dreams |

    # Check that only the user's chosen frequency is digested.
    But the weekly digest for hristo should not contain any messages
    And the monthly digest for hristo should not contain any messages
    And the daily digest for bisera should not contain any messages
    And the monthly digest for bisera should not contain any messages
    And the daily digest for kalin should not contain any messages
    And the weekly digest for kalin should not contain any messages

    # The digest should not include news about content that is not published.
    And the weekly digest for bisera should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
    And the monthly digest for kalin should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |

    # Publish an existing unpublished community content. It should be included
    # in the next digest.
    When the workflow state of the "Ruse" content is changed to "validated"

    Then the weekly digest for bisera should contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |

    # Check that the messages are formatted correctly.
    Given all message digests have been delivered
    Then the collection content subscription digest email sent to hristo contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Plovdiv              |
      | Stara Zagora         |
      | Products of Bulgaria |
      | Duck liver           |
      | Rose oil             |
      | Sunflower seeds      |

    And the collection content subscription digest email sent to bisera contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Burgas               |
      | Sofia                |
      | Stara Zagora         |
      | Products of Bulgaria |
      | Canned cherries      |
      | Rose oil             |

    And the collection content subscription digest email sent to kalin contains the following sections:
      | title                |
      | Cities of Bulgaria   |
      | Burgas               |
      | Plovdiv              |
      | Sofia                |
      | Products of Bulgaria |
      | Canned cherries      |
      | Sunflower seeds      |

    # Clean out the message queue for the next test.
    And the mail collector cache is empty

    # Check that if community content is published a second time it is not
    # included in the next digest.
    When the workflow state of the "Ruse" content is changed to "draft"
    Then the weekly digest for bisera should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
    And the monthly digest for kalin should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
    When the workflow state of the "Ruse" content is changed to "validated"
    Then the weekly digest for bisera should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
    And the monthly digest for kalin should not contain the following message:
      | mail_subject | Ruse          |
      | mail_body    | Little Vienna |
