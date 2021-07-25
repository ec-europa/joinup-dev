@api
Feature: Community RSS feed.
  In order to stay up to date with community updates
  As a user of Joinup
  I want to subscribe to RSS feeds for each community

  Scenario: Community RSS feed.
    Given users:
      | Username | First name | Family name |
      | alejake  | Aleta      | Jakeman     |
      | forest   | Forest     | Robinson    |
      | otto     | Otto       | Drake       |
    And communities:
      | title             | state     |
      | Indigo Monkey     | validated |
      | Dreaded Scissors  | validated |
      | Remote Electrical | draft     |
    And community user memberships:
      | community       | user    | role        |
      | Indigo Monkey    | alejake | facilitator |
      | Indigo Monkey    | forest  | facilitator |
      | Dreaded Scissors | otto    | facilitator |
    And solutions:
      | title           | state     | author  | creation date          | community       |
      | Lantern Global  | validated | alejake | 2018-12-18 08:00 +0100 | Indigo Monkey    |
      | Proton Lonesome | validated | alejake | 2019-01-05 10:00 +0100 | Indigo Monkey    |
      | Shiny Ray       | validated | otto    | 2018-08-14 17:36 +0200 | Dreaded Scissors |
    And news content:
      | title                                   | body                                                     | state     | author  | created                | community       | solution        |
      | Monkeys favourite indigo amongst colors | Research results are out.                                | validated | alejake | 2019-01-21 12:36 +0100 | Indigo Monkey    |                 |
      | Proton lonesomeness reaches peak        | More than 200 thousand protons were interviewed.         | validated | forest  | 2019-01-07 12:00 +0100 |                  | Proton Lonesome |
      | New metal alloy improves scissors       | It improves sharpness but they are more subject to rust. | validated | otto    | 2018-04-11 09:00 +0200 | Dreaded Scissors |                 |
    And event content:
      | title                    | body                                                                                                                                                                                                                                                                                                                                                            | state     | author | created                | community       |
      | Banana tasting           | Testing more than 20 varities of bananas from all over the world.                                                                                                                                                                                                                                                                                               | validated | forest | 2018-09-14 07:36 +0200 | Indigo Monkey    |
      | Scissor sharpening party | <p>The place where to be if you want to keep <strong>cutting</strong> the paper at the best of your scissors <a href="http://www.example.com/">possibilities</a>.</p> <table><tr><td>Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta lectus sit amet nulla feugiat et viverra massa fringilla.</td></tr></table> | validated | otto   | 2017-11-26 14:18 +0100 | Dreaded Scissors |
    And document content:
      | title                  | body                                                     | state     | author  | created                | community    |
      | Indigo technical paper | All technical information about the rare indigo monkeys. | validated | alejake | 2016-05-30 12:21 +0200 | Indigo Monkey |
    And discussion content:
      | title                                          | body                                                                                   | state     | author  | created                | community    |
      | Is the indigo coloration caused by their food? | I was reading the technical paper and it seems their main food is the indigo cherries. | validated | alejake | 2019-01-21 13:00 +0100 | Indigo Monkey |
    And custom_page content:
      | title             | body                                            | state     | author | created                | community        |
      | Indigo variations | The four major tones of indigo are listed here. | validated | forest | 2017-10-15 18:30 +0200 | Indigo Monkey     |
      | List of devices   | Available remote electrical devices.            | validated |        | 2019-02-08 09:00 +0100 | Remote Electrical |

    When I am an anonymous user
    And I go to the homepage of the "Indigo Monkey" community
    Then the page should contain an RSS autodiscovery link with title "Latest updates from the Indigo Monkey community" pointing to "/community/indigo-monkey/feed.xml"
    And the page should contain 1 RSS autodiscovery link
    When I click "RSS feed" in the "Entity actions" region
    Then I should see a valid RSS feed
    And the RSS feed channel elements should be:
      | title       | Latest updates from the Indigo Monkey community                                                                   |
      | description | This feed contains the latest published content from the Indigo Monkey community, including the newest solutions. |
      | link        | /community/indigo-monkey                                                                                          |
    And the RSS feed should have 7 items
    And the RSS feed items should be:
      | title                                                      | link                                                                     | description                                                                            | publication date                | author          |
      | Discussion: Is the indigo coloration caused by their food? | /community/indigo-monkey/discussion/indigo-coloration-caused-their-food | I was reading the technical paper and it seems their main food is the indigo cherries. | Mon, 21 Jan 2019 13:00:00 +0100 | Aleta Jakeman   |
      | News: Monkeys favourite indigo amongst colors              | /community/indigo-monkey/news/monkeys-favourite-indigo-amongst-colors   | Research results are out.                                                              | Mon, 21 Jan 2019 12:36:00 +0100 | Aleta Jakeman   |
      | Solution: Proton Lonesome                                  | /community/indigo-monkey/solution/proton-lonesome                       |                                                                                        | Sat, 05 Jan 2019 10:00:00 +0100 | Aleta Jakeman   |
      | Solution: Lantern Global                                   | /community/indigo-monkey/solution/lantern-global                        |                                                                                        | Tue, 18 Dec 2018 08:00:00 +0100 | Aleta Jakeman   |
      | Event: Banana tasting                                      | /community/indigo-monkey/event/banana-tasting                           | Testing more than 20 varities of bananas from all over the world.                      | Fri, 14 Sep 2018 07:36:00 +0200 | Forest Robinson |
      | Custom page: Indigo variations                             | /community/indigo-monkey/indigo-variations                              | The four major tones of indigo are listed here.                                        | Sun, 15 Oct 2017 18:30:00 +0200 | Forest Robinson |
      | Document: Indigo technical paper                           | /community/indigo-monkey/document/indigo-technical-paper                | All technical information about the rare indigo monkeys.                               | Mon, 30 May 2016 12:21:00 +0200 | Aleta Jakeman   |

    When I go to the homepage of the "Dreaded Scissors" community
    Then the page should contain an RSS autodiscovery link with title "Latest updates from the Dreaded Scissors community" pointing to "/community/dreaded-scissors/feed.xml"
    And the page should contain 1 RSS autodiscovery link
    When I click "RSS feed" in the "Entity actions" region
    Then I should see a valid RSS feed
    And the RSS feed channel elements should be:
      | title       | Latest updates from the Dreaded Scissors community                                                                   |
      | description | This feed contains the latest published content from the Dreaded Scissors community, including the newest solutions. |
      | link        | /community/dreaded-scissors                                                                                          |
    And the RSS feed items should be:
      | title                                   | link                                                                | description                                                                                                                                                                                            | publication date                | author     |
      | Solution: Shiny Ray                     | /community/dreaded-scissors/solution/shiny-ray                     |                                                                                                                                                                                                        | Tue, 14 Aug 2018 17:36:00 +0200 | Otto Drake |
      | News: New metal alloy improves scissors | /community/dreaded-scissors/news/new-metal-alloy-improves-scissors | It improves sharpness but they are more subject to rust.                                                                                                                                               | Wed, 11 Apr 2018 09:00:00 +0200 | Otto Drake |
      # The 'Scissor sharpening party' is using the `content_editor` text format
      # which is not configured to wrap the result in <p> tags after stripping
      # the HTML from it. This is different from the other tests that use the
      # `plain_text` text format. In production all content will be using this
      # text format since all text is entered through the content editor.
      | Event: Scissor sharpening party         | /community/dreaded-scissors/event/scissor-sharpening-party         | The place where to be if you want to keep cutting the paper at the best of your scissors possibilities. Lorem ipsum dolor sit amet consectetur adipiscing elit. Etiam sed consectetur turpis. In porta | Sun, 26 Nov 2017 14:18:00 +0100 | Otto Drake |

    When I go to the homepage of the "Lantern Global" solution
    Then I should see the link "RSS feed" in the "Entity actions" region
    And the page should contain 1 RSS autodiscovery links

    When I am logged in as a facilitator of the "Remote Electrical" community
    And I go to the homepage of the "Remote Electrical" community
    Then I should not see the link "RSS feed" in the "Entity actions" region
    And the page should contain 0 RSS autodiscovery links
