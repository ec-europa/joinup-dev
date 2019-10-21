@api
Feature: Subscribing to community content in collections
  As a member of a collection
  I want to receive a periodic digest listing newly published content
  So that I can stay informed

  Background:
    Given the following collection:
      | title | Famous products of Bulgaria |
      | state | validated                   |
    And users:
      | Username | E-mail            | First name | Family name  | Notification frequency |
      | hristo   | hristo@example.bg | Hristo     | Draganov     | daily                  |
      | bisera   | bisera@example.bg | Bisera     | Kaloyancheva | weekly                 |
      | kalin    | kalin@primer.bg   | Kalin      | Antov        | monthly                |
    And the following collection user memberships:
      | collection                  | user   | roles |
      | Famous products of Bulgaria | hristo |       |
      | Famous products of Bulgaria | bisera |       |
      | Famous products of Bulgaria | kalin  |       |
    And the following collection content subscriptions:
      | collection                  | user   | subscriptions              |
      | Famous products of Bulgaria | hristo | discussion, event, news    |
      | Famous products of Bulgaria | bisera | discussion, document, news |
      | Famous products of Bulgaria | kalin  | document, event            |
    And all message digests have been delivered
    And the mail collector cache is empty

  @email
  Scenario: Receive a digest of content that is published in my collections
    # Todo: We also need to check that no notifications are sent for content
    # that is not yet published, or has been previously published.
    # See ISAICP-4980
    Given discussion content:
      | title      | body                      | collection                  | state     | author |
      | Duck liver | Rich buttery and delicate | Famous products of Bulgaria | validated | hristo |
    And document content:
      | title           | body                   | collection                  | state     | author |
      | Canned cherries | Sour cherries for pies | Famous products of Bulgaria | validated | bisera |
    And event content:
      | title           | body          | collection                  | state     | author |
      | Sunflower seeds | A tasty snack | Famous products of Bulgaria | validated | bisera |
    And news content:
      | title    | body                        | collection                  | state     | author |
      | Rose oil | A widely used essential oil | Famous products of Bulgaria | validated | bisera |

    Then the daily digest for hristo should contain the following message:
      | mail_subject | Duck liver                |
      | mail_body    | Rich buttery and delicate |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Sunflower seeds |
      | mail_body    | A tasty snack   |
    And the daily digest for hristo should contain the following message:
      | mail_subject | Rose oil                    |
      | mail_body    | A widely used essential oil |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Duck liver                |
      | mail_body    | Rich buttery and delicate |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Canned cherries        |
      | mail_body    | Sour cherries for pies |
    And the weekly digest for bisera should contain the following message:
      | mail_subject | Rose oil                    |
      | mail_body    | A widely used essential oil |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Canned cherries        |
      | mail_body    | Sour cherries for pies |
    And the monthly digest for kalin should contain the following message:
      | mail_subject | Sunflower seeds |
      | mail_body    | A tasty snack   |

    But the weekly digest for hristo should not contain any messages
    And the monthly digest for hristo should not contain any messages
    And the daily digest for bisera should not contain any messages
    And the monthly digest for bisera should not contain any messages
    And the daily digest for kalin should not contain any messages
    And the weekly digest for kalin should not contain any messages
