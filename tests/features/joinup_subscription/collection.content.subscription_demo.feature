@api
Feature: Collection content subscription DEMO
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

    When the workflow state of the "Ruse" content is changed to "validated"

    Given all message digests have been delivered
    Then I break
