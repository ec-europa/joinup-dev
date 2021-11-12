@api @group-e
Feature: Creating comments through the API
  In order to efficiently write tests for the Joinup platform
  As a backend developer
  I need to be able to programmatically create comments

  Scenario Outline: Creating comments on community content
    Given collection:
      | title | Hashrate, shares & workers |
      | state | validated                  |
    And <content type> content:
      | title                      | body                                  | collection                 | state     |
      | Current effective hashrate | Ethash is the proof of work algorithm | Hashrate, shares & workers | validated |
    And user:
      | Username | Vasundhara Guadarrama |
    And comments:
      | cid  | message             | author                | parent                     | created                   |
      | 9999 | RX 580 undervolting | Vasundhara Guadarrama | Current effective hashrate | 2017-08-21T09:08:36+02:00 |
    Given I am an anonymous user
    When I go to the content page of the type "<content type>" with the title "Current effective hashrate"
    Then I should see the following lines of text:
      | RX 580 undervolting        |
      | Hashrate, shares & workers |
      | Mon, 21/08/2017 - 09:08    |
    When I click "Permalink" in the "Comment" region
    Then the url should match "/comment/9999#comment-9999"


    Examples:
      | content type |
      | news         |
      | event        |
      | discussion   |
      | document     |
