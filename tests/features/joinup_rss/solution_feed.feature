@api
Feature: Solution RSS feed.
  In order to stay up to date with solution updates
  As a user of Joinup
  I want to subscribe to RSS feeds for each solution

  Scenario: Solution RSS feed.
    Given users:
      | Username | First name | Family name |
      | scorlan  | Sartin     | Corlan      |
    And collection:
      | title | Unrandomed collection |
      | state | validated             |
    And solutions:
      | title             | state     | author  | creation date          | collection            |
      | Lantern Domestic  | validated | scorlan | 2018-12-18 08:00 +0100 | Unrandomed collection |
      | Deuteron Lonesome | draft     | scorlan | 2019-01-05 10:00 +0100 | Unrandomed collection |
    And solution user memberships:
      | solution          | user    | role        |
      | Lantern Domestic  | scorlan | facilitator |
      | Deuteron Lonesome | scorlan | facilitator |
    And news content:
      | title                                  | body                                                     | state     | author  | created                | solution          |
      | Monkeys worst indigo amongst colors    | Research results are out.                                | validated | scorlan | 2019-01-21 12:36 +0100 | Lantern Domestic  |
      | Proton lonesomeness doesn't reach peak | More than 200 thousand protons were interviewed.         | validated | scorlan | 2019-01-07 12:00 +0100 | Deuteron Lonesome |
      | Old metal alloy improves scissors      | It improves sharpness but they are more subject to rust. | validated | scorlan | 2018-04-11 09:00 +0100 | Lantern Domestic  |
    And event content:
      | title                      | body                                                                                                                                                                                                                                                                                                                                                            | state     | author  | created                | solution         |
      | Scissor sharpening funeral | <p>The place where to be if you want to keep <strong>cutting</strong> the paper at the best of your scissors <a href="http://www.example.com/">possibilities</a>.</p> <table><tr><td>Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta lectus sit amet nulla feugiat et viverra massa fringilla.</td></tr></table> | validated | scorlan | 2017-11-26 14:18 +0100 | Lantern Domestic |
    And discussion content:
      | title                                           | body                                                                                   | state     | author  | created                | solution          |
      | Is the indigo coloration caused by their smile? | I was reading the technical paper and it seems their main food is the indigo cherries. | validated | scorlan | 2019-01-21 13:00 +0100 | Deuteron Lonesome |
    And custom_page content:
      | title             | body                                            | state     | author  | created                | solution          |
      | Indigo variations | The four major tones of indigo are listed here. | validated | scorlan | 2017-10-15 19:30 +0200 | Lantern Domestic  |
      | List of devices   | Available remote electrical devices.            | validated | scorlan | 2019-02-08 10:00 +0200 | Deuteron Lonesome |

    When I am an anonymous user
    And I go to the homepage of the "Lantern Domestic" solution
    Then the page should contain an RSS autodiscovery link with title "Latest updates from the Lantern Domestic solution" pointing to "/collection/unrandomed-collection/solution/lantern-domestic/feed.xml"
    And the page should contain 1 RSS autodiscovery link
    When I click "RSS feed" in the "Entity actions" region
    Then I should see a valid RSS feed
    And the RSS feed channel elements should be:
      | title       | Latest updates from the Lantern Domestic solution                                   |
      | description | This feed contains the latest published content from the Lantern Domestic solution. |
      | link        | /collection/unrandomed-collection/solution/lantern-domestic                         |
    And the RSS feed should have 4 items
    And the RSS feed items should be:
      | title                                     | link                                                                                                 | description                                                                                                                                                                                            | publication date                | author        |
      | News: Monkeys worst indigo amongst colors | /collection/unrandomed-collection/solution/lantern-domestic/news/monkeys-worst-indigo-amongst-colors | Research results are out.                                                                                                                                                                              | Mon, 21 Jan 2019 12:36:00 +0100 | Sartin Corlan |
      | News: Old metal alloy improves scissors   | /collection/unrandomed-collection/solution/lantern-domestic/news/old-metal-alloy-improves-scissors   | It improves sharpness but they are more subject to rust.                                                                                                                                               | Wed, 11 Apr 2018 10:00:00 +0200 | Sartin Corlan |
      | Event: Scissor sharpening funeral         | /collection/unrandomed-collection/solution/lantern-domestic/event/scissor-sharpening-funeral         | The place where to be if you want to keep cutting the paper at the best of your scissors possibilities. Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta | Sun, 26 Nov 2017 14:18:00 +0100 | Sartin Corlan |
      | Custom page: Indigo variations            | /collection/unrandomed-collection/solution/lantern-domestic/indigo-variations                        | The four major tones of indigo are listed here.                                                                                                                                                        | Sun, 15 Oct 2017 19:30:00 +0200 | Sartin Corlan |

    When I am logged in as a facilitator of the "Deuteron Lonesome" solution
    And I go to the homepage of the "Deuteron Lonesome" solution
    Then I should not see the link "RSS feed" in the "Entity actions" region
    And the page should contain 0 RSS autodiscovery links
