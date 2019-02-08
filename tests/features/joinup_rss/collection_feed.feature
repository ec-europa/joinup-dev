@api
Feature: Collection RSS feed.
  In order to stay up to date with collection updates
  As a user of Joinup
  I want to subscribe to RSS feeds for each collection

  Scenario: Collection RSS feed.
    Given users:
      | Username | First name | Family name |
      | alejake  | Aleta      | Jakeman     |
      | forest   | Forest     | Robinson    |
      | otto     | Otto       | Drake       |
    And collections:
      | title             | state     |
      | Indigo Monkey     | validated |
      | Dreaded Scissors  | validated |
      | Remote Electrical | draft     |
    And collection user memberships:
      | collection       | user    | role        |
      | Indigo Monkey    | alejake | facilitator |
      | Indigo Monkey    | forest  | facilitator |
      | Dreaded Scissors | otto    | facilitator |
    And solutions:
      | title           | state     | author  | creation date    | collection       |
      | Lantern Global  | validated | alejake | 2018-12-18 08:00 | Indigo Monkey    |
      | Proton Lonesome | validated | alejake | 2019-01-05 10:00 | Indigo Monkey    |
      | Shiny Ray       | validated | otto    | 2018-08-14 17:36 | Dreaded Scissors |
    And news content:
      | title                                   | body                                                     | state     | author  | created          | collection       | solution        |
      | Monkeys favourite indigo amongst colors | Research results are out.                                | validated | alejake | 2019-01-21 12:36 | Indigo Monkey    |                 |
      | Proton lonesomeness reaches peak        | More than 200 thousand protons were interviewed.         | validated | forest  | 2019-01-07 12:00 |                  | Proton Lonesome |
      | New metal alloy improves scissors       | It improves sharpness but they are more subject to rust. | validated | otto    | 2018-04-11 09:00 | Dreaded Scissors |                 |
    And event content:
      | title                    | body                                                                                                                                                                                                                                                                                                                                                                                            | state     | author | created          | collection       |
      | Banana tasting           | Testing more than 20 varities of bananas from all over the world.                                                                                                                                                                                                                                                                                                                               | validated | forest | 2018-09-14 07:36 | Indigo Monkey    |
      | Scissor sharpening party | value: <p>The place where to be if you want to keep <strong>cutting</strong> the paper at the best of your scissors <a href="http://www.example.com/">possibilities</a>.</p> <table><tr><td>Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta lectus sit amet nulla feugiat et viverra massa fringilla.</td></tr></table> - format: content_editor | validated | otto   | 2017-11-26 14:18 | Dreaded Scissors |
    And document content:
      | title                  | body                                                     | state     | author  | created          | collection    |
      | Indigo technical paper | All technical information about the rare indigo monkeys. | validated | alejake | 2016-05-30 12:21 | Indigo Monkey |
    And discussion content:
      | title                                          | body                                                                                   | state     | author  | created          | collection    |
      | Is the indigo coloration caused by their food? | I was reading the technical paper and it seems their main food is the indigo cherries. | validated | alejake | 2019-01-21 13:00 | Indigo Monkey |
    And custom_page content:
      | title             | body                                            | state     | author | created          | collection        |
      | Indigo variations | The four major tones of indigo are listed here. | validated | forest | 2017-10-15 18:30 | Indigo Monkey     |
      | List of devices   | Available remote electrical devices.            | validated |        | 2019-02-08 09:00 | Remote Electrical |

    When I am an anonymous user
    And I go to the homepage of the "Indigo Monkey" collection
    And I click "RSS feed" in the "Entity actions" region
    Then I should see a valid RSS feed
    And the RSS feed channel elements should be:
      | title       | Latest updates from the Indigo Monkey collection                                                                   |
      | description | This feed contains the latest published content from the Indigo Monkey collection, including the newest solutions. |
      | link        | /collection/indigo-monkey/feed.xml                                                                                 |
    And the RSS feed should have 7 items
    And the RSS feed items should be:
      | title                                                      | link                                            | description                                                                                   | publication date                | author          |
      | Discussion: Is the indigo coloration caused by their food? | /discussion/indigo-coloration-caused-their-food | <p>I was reading the technical paper and it seems their main food is the indigo cherries.</p> | Mon, 21 Jan 2019 13:00:00 +0100 | Aleta Jakeman   |
      | News: Monkeys favourite indigo amongst colors              | /news/monkeys-favourite-indigo-amongst-colors   | <p>Research results are out.</p>                                                              | Mon, 21 Jan 2019 12:36:00 +0100 | Aleta Jakeman   |
      | Solution: Proton Lonesome                                  | /solution/proton-lonesome                       |                                                                                               | Sat, 05 Jan 2019 10:00:00 +0100 | Aleta Jakeman   |
      | Solution: Lantern Global                                   | /solution/lantern-global                        |                                                                                               | Tue, 18 Dec 2018 08:00:00 +0100 | Aleta Jakeman   |
      | Event: Banana tasting                                      | /event/banana-tasting                           | <p>Testing more than 20 varities of bananas from all over the world.</p>                      | Fri, 14 Sep 2018 07:36:00 +0200 | Forest Robinson |
      | Custom page: Indigo variations                             | /collection/indigo-monkey/indigo-variations     | <p>The four major tones of indigo are listed here.</p>                                        | Sun, 15 Oct 2017 18:30:00 +0200 | Forest Robinson |
      | Document: Indigo technical paper                           | /document/indigo-technical-paper                | <p>All technical information about the rare indigo monkeys.</p>                               | Mon, 30 May 2016 12:21:00 +0200 | Aleta Jakeman   |

    When I go to the homepage of the "Dreaded Scissors" collection
    And I click "RSS feed" in the "Entity actions" region
    Then I should see a valid RSS feed
    And the RSS feed channel elements should be:
      | title       | Latest updates from the Dreaded Scissors collection                                                                   |
      | description | This feed contains the latest published content from the Dreaded Scissors collection, including the newest solutions. |
      | link        | /collection/dreaded-scissors/feed.xml                                                                                 |
    And the RSS feed items should be:
      | title                                   | link                                    | description                                                                                                                                                                                                                                                                                           | publication date                | author     |
      | Solution: Shiny Ray                     | /solution/shiny-ray                     |                                                                                                                                                                                                                                                                                                       | Tue, 14 Aug 2018 17:36:00 +0200 | Otto Drake |
      | News: New metal alloy improves scissors | /news/new-metal-alloy-improves-scissors | <p>It improves sharpness but they are more subject to rust.</p>                                                                                                                                                                                                                                       | Wed, 11 Apr 2018 09:00:00 +0200 | Otto Drake |
      | Event: Scissor sharpening party         | /event/scissor-sharpening-party         | <p>The place where to be if you want to keep <strong>cutting</strong> the paper at the best of your scissors <a href="http://www.example.com/">possibilities</a>.</p> <table><tr><td>Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta</td></tr></table> | Sun, 26 Nov 2017 14:18:00 +0100 | Otto Drake |

    When I go to the homepage of the "Remote Electrical" collection
    Then I should not see the link "RSS feed" in the "Entity actions" region

    When I go to the homepage of the "Lantern Global" solution
    Then I should not see the link "RSS feed" in the "Entity actions" region
