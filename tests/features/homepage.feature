@api
Feature: Homepage
  In order to present a good introduction of the website to a new visitor
  As a product owner
  I want to highlight the most important sections on the homepage

  Scenario: The homepage should be cacheable
    Given I am not logged in
    And I am on the homepage
    When I reload the page
    Then the page should be cached
    And I should see the following lines of text:
      | Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme.    |
      | Joinup offers several services that aim to help e-Government professionals share their experience with each other. Joinup supports them to find, choose, re-use, develop and implement interoperability solutions. |
      | For fresh start on creating content                                                                                                                                                                                |
    And I should see the following links:
      | How to video |
      | Guided tour  |
      | FAQ          |

  Scenario: Only specific social network links are available in the footer.
    When I am on the homepage
    Then I should see the link "LinkedIn" in the Footer region
    And the "LinkedIn" link should point to "https://www.linkedin.com/groups/2600644/"
    And I should see the link "Twitter" in the Footer region
    But I should not see the link "Facebook" in the Footer region

  @terms
  Scenario: Latest news is shown on the homepage
    Given collection:
      | title | Shaping of nature |
      | state | validated         |
    And news content:
      | title                       | headline                 | collection        | topic                                      | state     | publication date     | body                                                                                                                                                                                                                                                 |
      | Current biodiversity crisis | Preserve habitats        | Shaping of nature | Finance in EU, Supplier exchange, E-health | validated | 2021-04-26T19:09:00Z | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by indigenous peoples. |
      | Environmental stewardship   | Transformative practices | Shaping of nature | Employment and Support Allowance           | validated | 2021-01-27T16:12:00Z | With rare exceptions current biodiversity losses are caused not by human conversion or degradation of untouched ecosystems but rather by the appropriation colonization and intensification of use in lands inhabited and used by prior societies.   |
      | Spatial reconstruction      | Loss of wildlands        | Shaping of nature | HR, Statistics and Analysis, E-justice     | validated | 2021-02-28T13:15:00Z | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                            |
      | Earlier transformations     | Ecosystem management     | Shaping of nature | EU and European Policies                   | validated | 2021-03-29T10:18:00Z | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.     |
    When I am on the homepage
    Then the latest news section should contain the following news articles:
      | date   | topics                           | title                       | body                                                                                                                                                                                                                                                 |
      | 26 Apr | Finance in EU, Supplier exchange | Current biodiversity crisis | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by indigenous peoples. |
      | 29 Mar | EU and European Policies         | Earlier transformations     | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.     |
      | 28 Feb | HR, Statistics and Analysis      | Spatial reconstruction      | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                            |

    # The topics that are associated with the news articles should redirect to a
    # search page which is pre-filtered on the topic.
    # Todo: Once topics have canonical pages these should redirect to the topic
    #   page instead.
    When I click "Finance in EU"
    Then I should be on the advanced search page
    And the option with text "Finance in EU" from select facet "topic" is selected

  @terms
  Scenario: Community content can be placed "In the spotlight" on the homepage
    Given collection:
      | title | Mice in space |
      | state | validated     |
    And news content:
      | title          | collection    | topic                                      | state     | logo       | body                                                                                                                                                                              |
      | Muscle atrophy | Mice in space | Finance in EU, Supplier exchange, E-health | validated | blaise.jpg | Researchers from the University of Tsukuba have sent mice into space to explore effects of spaceflight and reduced gravity on muscle atrophy, or wasting, at the molecular level. |
    And discussion content:
      | title                | collection    | topic                            | state     | logo    | body                                                                                                                                                                                       |
      | Influence of gravity | Mice in space | Employment and Support Allowance | validated | ada.jpg | Space exploration has brought about many scientific and technological advances, yet manned spaceflights come at a cost to astronauts, including reduced skeletal muscle mass and strength. |
    And document content:
      | title        | collection    | topic                                  | state     | logo     | body                                                                                                                                                                                            |
      | Microgravity | Mice in space | HR, Statistics and Analysis, E-justice | validated | alan.jpg | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |
    And event content:
      | title           | collection    | topic                    | state     | logo        | body                                                                                                                                                                                  |
      | Stay at the ISS | Mice in space | EU and European Policies | validated | charles.jpg | Two groups of mice (six per group) were housed aboard the International Space Station for 35 days. One group was subjected to artificial gravity (1 g) and the other to microgravity. |
    And the "In the spotlight" content listing contains:
      | type    | label           |
      | content | Muscle atrophy  |
      | content | Stay at the ISS |
      | content | Microgravity    |

    When I am on the homepage
    Then the in the spotlight section should contain the following content:
      | number | logo        | topics                           | title           | body                                                                                                                                                                                            |
      | 1      | blaise.jpg  | Finance in EU, Supplier exchange | Muscle atrophy  | Researchers from the University of Tsukuba have sent mice into space to explore effects of spaceflight and reduced gravity on muscle atrophy                                                    |
      | 2      | charles.jpg | EU and European Policies         | Stay at the ISS | Two groups of mice (six per group) were housed aboard the International Space Station for 35 days. One group was subjected to artificial gravity (1 g) and the other to microgravity.           |
      | 3      | alan.jpg    | HR, Statistics and Analysis      | Microgravity    | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |

  @terms
  Scenario: A solution can be highlighted on the homepage
    Given solution:
      | title         | Mercury poisoning                                                          |
      | state         | validated                                                                  |
      | description   | Mercury poisoning is a type of metal poisoning due to exposure to mercury. |
      | logo          | logo.png                                                                   |
      | solution type | Interoperability Specification, Networking Service                         |
      | topic         | Demography, EU and European Policies, HR                                   |
      | state         | validated                                                                  |
    And the "Highlighted solution" content listing contains:
      | type     | label             |
      | solution | Mercury poisoning |

    When I am on the homepage
    Then I should see "Mercury poisoning" as the highlighted solution

  Scenario: An event can be highlighted on the homepage
    Given event content:
      | title                     | start date       | end date         | state     |
      | Florentine steak festival | 2021-06-04T20:00 | 2021-06-04T22:00 | validated |
    And the "Highlighted event" content listing contains:
      | type    | label                     |
      | content | Florentine steak festival |
    And the "Highlighted event" content listing has the following fields:
      | header text  | We don't serve 'well done' |
      | link text    | Eat our divine meat        |
      | external url | http://raresteaktown.com/  |

    When I am on the homepage
    Then I should see the heading "We don't serve 'well done'" in the "Highlighted event"
    And I should see the heading "Florentine steak festival" in the "Highlighted event"
    And I should see the text "04 to 05/06/2021" in the "Highlighted event"
    And I should see the link "Eat our divine meat" in the "Highlighted event"
    And the response should contain "http://raresteaktown.com"
    And I should see the link "More events" in the "Highlighted event"

    # When all the fields in the Highlighted event content listing are left
    # empty, a "Read more" link to the event page should be shown by default.
    Given the "Highlighted event" content listing has the following fields:
      | header text  | |
      | link text    | |
      | external url | |
    When I am on the homepage
    And I should see the heading "Florentine steak festival" in the "Highlighted event"
    And I should see the text "04 to 05/06/2021" in the "Highlighted event"
    And I should see the link "Read more" in the "Highlighted event"
    And I should see the link "More events" in the "Highlighted event"
    But I should not see the heading "We don't serve 'well done'"
    And the response should not contain "http://raresteaktown.com"

    When I click "Read more" in the "Highlighted event"
    Then the url should match "/collection/joinup/event/florentine-steak-festival"

    # The "More events" link should temporarily link to the search page with the
    # events pre-filtered. This will be replaced with the events page later.
    Given I am on the homepage
    When I click "More events"
    Then I should be on the advanced search page
    And the "Event" content checkbox item should be selected
    And I should see the "Florentine steak festival" tile

  @version
  Scenario Outline: The current version of the Joinup platform is shown in the footer.
    Given the Joinup version is set to "<version>"
    When I am on the homepage
    Then I should see the link "<version>" in the Footer region
    When I click "<version>"
    Then the url should match "<url>"

    Examples:
      | version                    | url                                        |
      | v1.57.0                    | /ec-europa/joinup-dev/releases/tag/v1.57.0 |
      | v1.57.0-177-g0123456abcdef | /ec-europa/joinup-dev/commit/0123456abcdef |

  Scenario: Search box is shown in the main content instead of the header on the homepage
    Given collection:
      | title | RNA vaccines |
      | state | validated    |
    And I am on the homepage
    Then I should see the "Search" field in the Content region
    And I should see the button "Search" in the Content region
    But I should not see the "Search" field in the Header region
    And I should not see the button "Search" in the Header region
    When I enter "RNA" in the search bar
    And press "Search"
    Then I should be on the search page
    And I should see the "RNA vaccines" tile

  @terms
  Scenario: Discover topics block shows a list of topics.
    Given collection:
      | title | Clash of vania's |
      | state | validated        |
    And news content:
      | title      | headline      | collection       | topic     | state     | publication date     | body |
      | Some title | Some headline | Clash of vania's | E-justice | validated | 2021-04-26T19:09:00Z | Body |
    And the "Discover topics" content listing contains:
      | type  | label                            |
      | topic | Employment and Support Allowance |
      | topic | E-justice                        |
    When I am on the homepage
    Then I should see the link "Employment and Support Allowance" in the "Discover topics block"
    And I should see the link "E-justice" in the "Discover topics block"
    When I click "E-justice"
    Then I should be on the advanced search page
    And the option with text "E-justice" from select facet "topic" is selected

  @terms
  Scenario: Explore block shows a list of news.
    Given users:
      | Username         | E-mail                   |
      | Quim Roscas      | quim.roscas@example.com  |
      | Josse Malhoa     | josse.malhoa@example.com |
    And collection:
      | title | Clash of jonnys |
      | state | validated       |
    And solutions:
      | title                  | state     | author       | creation date          | collection      |
      | Lantern International  | validated | Quim Roscas  | 2018-12-18 08:00 +0100 | Clash of jonnys |
      | Proton Global          | validated | Josse Malhoa | 2019-01-05 10:00 +0100 | Clash of jonnys |
      | Shiny Shan             | validated | Josse Malhoa | 2018-08-14 17:36 +0200 | Clash of jonnys |
    And news content:
      | title                       | headline                 | collection       | topic                                      | state     | publication date     | body                                                                                                                                                                                                                                                 |
      | Current biodiversity adapt  | Adapt habitats           | Clash of jonnys  | Finance in EU, Supplier exchange, E-health | validated | 2021-04-26T19:09:00Z | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by indigenous peoples. |
      | Environmental tests         | Test practices           | Clash of jonnys  | Employment and Support Allowance           | validated | 2021-01-27T16:12:00Z | With rare exceptions current biodiversity losses are caused not by human conversion or degradation of untouched ecosystems but rather by the appropriation colonization and intensification of use in lands inhabited and used by prior societies.   |
      | Spatial construction        | Win of wildlands         | Clash of jonnys  | HR, Statistics and Analysis, E-justice     | validated | 2021-02-28T13:15:00Z | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                            |
      | Easier transformations      | Ecosystem                | Clash of jonnys  | EU and European Policies                   | validated | 2021-03-29T10:18:00Z | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.     |
    And event content:
      | title                        | body               | collection      | solution               | state            |
      | Thick-target collisional     | Evaporation        | Clash of jonnys | Lantern International  | validated        |
      | Conductive cooling losses    | Single-loop        | Clash of jonnys | Proton Global          | validated        |
      | Source of SXR plasma supply  | Fast electrons     | Clash of jonnys | Shiny Shan             | validated        |
      | Stars forming in material    | Colossal material  | Clash of jonnys | Shiny Shan             | validated        |
    When I am on the homepage
    Then I should see the button "Solutions" in the "Explore block"
    And I should see the button "Collections" in the "Explore block"
    And I should see the button "News" in the "Explore block"
    And I should see the button "Events" in the "Explore block"
    And I should see the link "See more" in the "Explore block"
    When I click "See more"
    Then I should be on the advanced search page
    And the "Solutions" content checkbox item should be selected
