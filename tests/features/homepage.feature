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
    And I should see the text "Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme. It offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions."

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
    And the option with text "Finance in EU (1)" from select facet "topic" is selected

  @terms
  Scenario: Community content can be placed "In the spotlight" on the homepage
    Given collection:
      | title | Mice in space |
      | state | validated     |
    And news content:
      | title          | collection    | topic                                      | state     | body                                                                                                                                                                              |
      | Muscle atrophy | Mice in space | Finance in EU, Supplier exchange, E-health | validated | Researchers from the University of Tsukuba have sent mice into space to explore effects of spaceflight and reduced gravity on muscle atrophy, or wasting, at the molecular level. |
    And discussion content:
      | title                | collection    | topic                            | state     | body                                                                                                                                                                                       |
      | Influence of gravity | Mice in space | Employment and Support Allowance | validated | Space exploration has brought about many scientific and technological advances, yet manned spaceflights come at a cost to astronauts, including reduced skeletal muscle mass and strength. |
    And document content:
      | title        | collection    | topic                                  | state     | body                                                                                                                                                                                            |
      | Microgravity | Mice in space | HR, Statistics and Analysis, E-justice | validated | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |
    And event content:
      | title           | collection    | topic                    | state     | body                                                                                                                                                                                  |
      | Stay at the ISS | Mice in space | EU and European Policies | validated | Two groups of mice (six per group) were housed aboard the International Space Station for 35 days. One group was subjected to artificial gravity (1 g) and the other to microgravity. |

    When I am on the homepage
    Then the spotlight section should contain the following content:
      | number | topics                           | title           | body                                                                                                                                                                                                                                                 |
      | 1      | Finance in EU, Supplier exchange | Muscle atrophy  | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by indigenous peoples. |
      | 2      | EU and European Policies         | Stay at the ISS | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.     |
      | 3      | HR, Statistics and Analysis      | Microgravity    | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                            |

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
