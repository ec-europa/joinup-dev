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
      | The European Commission created Joinup to provide a common venue that enables public administrations, businesses and citizens to share and reuse IT solutions and good practices, and facilitate communication and collaboration on IT projects across Europe. |
      | Joinup offers several services that aim to help e-Government professionals share their experience with each other. Joinup supports them to find, choose, re-use, develop and implement interoperability solutions.                                             |
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
      | title                       | headline                 | collection        | topic                                      | state     | publication date     | body                                                                                                                                                                                                                                                          |
      | Current biodiversity crisis | Preserve habitats        | Shaping of nature | Finance in EU, Supplier exchange, E-health | validated | 2021-04-26T19:09:00Z | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly <em>three quarters of nature has long been shaped by histories of human habitation</em> and use by indigenous peoples. |
      | Environmental stewardship   | Transformative practices | Shaping of nature | Employment and Support Allowance           | validated | 2021-01-27T16:12:00Z | With rare exceptions current biodiversity losses are caused not by human conversion or degradation of untouched ecosystems but rather by the appropriation colonization and intensification of use in lands inhabited and used by prior societies.            |
      | Spatial reconstruction      | Loss of wildlands        | Shaping of nature | HR, Statistics and Analysis, E-justice     | validated | 2021-02-28T13:15:00Z | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                                     |
      | Earlier transformations     | Ecosystem management     | Shaping of nature | EU and European Policies                   | validated | 2021-03-29T10:18:00Z | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.              |
    When I am on the homepage
    Then the latest news section should contain the following news articles:
      | date   | topics                           | title                       | body                                                                                                                                                                                                     |
      | 26 Apr | Finance in EU, Supplier exchange | Current biodiversity crisis | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of…    |
      | 29 Mar | EU and European Policies         | Earlier transformations     | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left… |
      | 28 Feb | HR, Statistics and Analysis      | Spatial reconstruction      | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                |

    # The topics that are associated with the news articles should redirect to a
    # search page which is pre-filtered on the topic.
    # Todo: Once topics have canonical pages these should redirect to the topic
    #   page instead.
    When I click "Finance in EU"
    Then I should be on the advanced search page
    And the option with text "Finance in EU" from select facet "topic" is selected

    # There is a "More news" link that for the moment leads to the search page
    # pre-filtered on news articles. In the future this will become a dedicated
    # page showing all the news on Joinup.
    Given I am on the homepage
    When I click "More news"
    Then I should be on the advanced search page
    And the "News" content checkbox item should be selected
    And the page should show the tiles "Current biodiversity crisis, Environmental stewardship, Spatial reconstruction, Earlier transformations"

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
  Scenario Outline: Community content be highlighted on the homepage
    Given collection:
      | title | Clash of documents |
      | state | validated          |
    And <type> content:
      | title        | collection         | topic                              | state     | logo     | body                                                                                                                                                                                            |
      | Microgravity | Clash of documents | HR, E-justice                      | validated | alan.jpg | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |
      | Aliens       | Clash of documents | Statistics and Analysis, E-justice | validated | alan.jpg | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |
      | Groundforce  | Clash of documents | Statistics and Analysis, E-justice | proposed  | alan.jpg | Conventional studies investigating the effects of reduced gravity on muscle mass and function have used a ground control group that is not directly comparable to the space experimental group. |
    And the "Highlighted content" content listing contains:
      | type   | label  |
      | <type> | Aliens |

    When I am on the homepage
    Then I should see "Aliens" as the Highlighted content
    And I should see the link "Related content"
    When I click "Related content"
    Then I should be on the advanced search page
    And the <label> content checkbox item should be selected
    And I should see the "Aliens" tile
    And I should not see the "Microgravity" tile
    And I should not see the "Groundforce" tile

    Examples:
      | type     | label    |
      | document | Document |
      | news     | News     |
      | event    | Event    |

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
      | header text  |  |
      | link text    |  |
      | external url |  |
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

  Scenario: Search box is shown in the main content
    Given collection:
      | title | RNA vaccines |
      | state | validated    |
    And I am on the homepage
    Then I should see the "Search" field in the Featured region
    And I should see the button "Search" in the Featured region
    When I enter "RNA" in the search bar
    And press "Search"
    Then I should be on the search page
    And I should see the "RNA vaccines" tile

  # Todo: This test is disabled because of a persisting failure on CPHP which
  # cannot be replicated locally. To be enabled again once we have moved to
  # the new infrastructure.
  # See https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6656
  @terms @javascript @wip
  Scenario Outline: Discover topics block shows a list of topics.
    Given collection:
      | title | Clash of vania's |
      | state | validated        |
    And news content:
      | title             | headline                      | collection       | topic         | state     | publication date     | body                  |
      | Some title        | Some headline                 | Clash of vania's | E-justice     | validated | 2021-04-26T19:09:00Z | Body                  |
      | Internet medicine | It cures virtually everything | Clash of vania's | E-health Dpt. | validated | 2014-02-22T19:26:57Z | Electronic healthcare |
    And the "Discover topics" content listing contains:
      | type  | label                            |
      | topic | Employment and Support Allowance |
      | topic | E-justice                        |
    When I <logged in>
    And I am on the homepage
    Then I should see the link "Employment and Support Allowance" in the "Discover topics block"
    And I should see the link "E-justice" in the "Discover topics block"
    When I click "E-justice"
    Then I should be on the advanced search page
    And the option with text "E-justice" from select facet "topic" is selected

    # See more topics modal.
    When I am on the homepage
    Then I should see the link "See more topics" in the "Discover topics block"
    And I should not see the text "Topic categories"
    And I should not see the following links in the "Discover topics block":
      | Economy and Welfare  |
      | eGov                 |
      | E-health Dpt.        |
      | HR Dpt.              |
      | Info                 |
      | Law and Justice      |
      | Social and Political |

    When I click "See more topics" in the "Discover topics block"
    Then a modal should open
    And I should see the text "Topic categories"
    And I should see the following links:
      | Economy and Welfare  |
      | eGov                 |
      | E-health Dpt.        |
      | HR Dpt.              |
      | Info                 |
      | Law and Justice      |
      | Social and Political |
    When I click "E-health Dpt." in the "Modal content"
    Then I should be on the advanced search page
    And the option with text "E-health Dpt." from select facet "topic" is selected
    And the page should show the tiles "Internet medicine"

    Examples:
      | logged in                                          |
      | am not logged in                                   |
      | am logged in as a user with the authenticated role |

  @terms
  Scenario: Explore block shows a selection of news, events, collections and solutions
    Given users:
      | Username     | E-mail                   |
      | Quim Roscas  | quim.roscas@example.com  |
      | Josse Malhoa | josse.malhoa@example.com |
    And collections:
      | title           | state     | description                                                                                                                                                                                                                                                                                                                                                                                                  | creation date          |
      | Clash of jonnys | validated | Supports <a href="#">health-related</a> fields                                                                                                                                                                                                                                                                                                                                                               | 2019-12-18 08:00 +0100 |
      | Clash of alex   | validated | Lorem Ipsum is simply dummy                                                                                                                                                                                                                                                                                                                                                                                  | 2018-11-18 08:00 +0100 |
      | Clash of vettel | validated | The point is that we <strong>do</strong> have the rights to one of our films. So we could be dealing with a film that we'd normally have to show only for three or four weeks and see what happens. But it's more like a TV show. It's bigger and has greater distribution potential.                                                                                                                        | 2020-10-18 08:00 +0100 |
      | Clash of olive  | draft     | Typesetting industry                                                                                                                                                                                                                                                                                                                                                                                         | 2020-09-18 08:00 +0100 |
      | Clash of mars   | proposed  | Simply dummy                                                                                                                                                                                                                                                                                                                                                                                                 | 2020-08-18 08:00 +0100 |
      | Nature area     | validated | Supports health                                                                                                                                                                                                                                                                                                                                                                                              | 2018-07-18 08:00 +0100 |
      | Sarah desert    | validated | "<p>Watch our video!</p><p>{""preview_thumbnail"":""/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/dQw4w9WgXcQ.jpg?itok=s9z0clFM"",""video_url"":""https://www.youtube.com/watch?v=dQw4w9WgXcQ"",""settings"":{""responsive"":true,""width"":""854"",""height"":""480"",""autoplay"":false},""settings_summary"":[""Embedded Video (Responsive).""]}<br>It's embedded!</p>" | 2020-06-18 08:00 +0100 |
      | Ice is down     | draft     | Content here! content here                                                                                                                                                                                                                                                                                                                                                                                   | 2020-05-18 08:00 +0100 |
      | Sunset ball     | validated | There are many variations                                                                                                                                                                                                                                                                                                                                                                                    | 2019-04-18 08:00 +0100 |
      | Sunset stream   | validated | There are many passages                                                                                                                                                                                                                                                                                                                                                                                      | 2019-02-18 08:00 +0100 |
      | Iceland crown   | proposed  | Lorem Ipsum is therefore                                                                                                                                                                                                                                                                                                                                                                                     | 2019-11-18 08:00 +0100 |
      | Jupiter sun     | validated | Sum is therefore                                                                                                                                                                                                                                                                                                                                                                                             | 2018-12-18 08:00 +0100 |
    And solutions:
      | title                 | state     | author       | creation date          | collection      | description                                                                                                                                                                                                                                                                                                                                                                         |
      | Lantern International | validated | Quim Roscas  | 2018-12-18 08:00 +0100 | Clash of jonnys | Lantern International Group has started deploying personnel <em>in focused areas</em>. In this quest we have decided to consolidate our private businesses into one entity as a significant step towards realizing our company's long-term plan.                                                                                                                                    |
      | Proton Global         | validated | Josse Malhoa | 2019-07-05 10:00 +0100 | Clash of jonnys | <p>An inlined image:</p><img alt="Something interesting" data-align="center" data-caption="The image caption" data-entity-type="file" data-entity-uuid="81e91d97-a273-43c6-8f2f-2f5beb38fbf7" data-image-style="wysiwyg_full_width" height="640" src="/sites/default/files/inline-images/image.jpg" width="480" /><p>Text after image.</p>                                          |
      | Shiny Shan            | draft     | Josse Malhoa | 2018-06-14 17:36 +0200 | Clash of jonnys | Text of the printing                                                                                                                                                                                                                                                                                                                                                                |
      | Spherification        | proposed  | Quim Roscas  | 2018-05-14 17:36 +0200 | Sarah desert    | <p>An inlined image:</p><img alt="Something interesting" data-align="center" data-caption="The image caption" data-entity-type="file" data-entity-uuid="81e91d97-a273-43c6-8f2f-2f5beb38fbf7" data-image-style="wysiwyg_full_width" height="640" src="/sites/default/files/inline-images/image.jpg" width="480" /><p>Text after image.</p>                                          |
      | Foam                  | draft     | Josse Malhoa | 2018-03-14 17:36 +0200 | Clash of jonnys | Simply dummy                                                                                                                                                                                                                                                                                                                                                                        |
      | Products of Bulgaria  | validated | Quim Roscas  | 2018-08-14 17:36 +0200 | Jupiter sun     | Supports health                                                                                                                                                                                                                                                                                                                                                                     |
      | Cities of Bulgaria    | validated | Josse Malhoa | 2018-11-14 17:36 +0200 | Clash of jonnys | Supports fields                                                                                                                                                                                                                                                                                                                                                                     |
      | Products of France    | proposed  | Josse Malhoa | 2018-11-04 17:36 +0200 | Sarah desert    | Content here! content here                                                                                                                                                                                                                                                                                                                                                          |
      | Cities of France      | validated | Josse Malhoa | 2018-12-14 17:36 +0200 | Clash of jonnys | There are many variations                                                                                                                                                                                                                                                                                                                                                           |
      | Products of Portugal  | draft     | Quim Roscas  | 2019-08-14 17:36 +0200 | Jupiter sun     | There are many passages                                                                                                                                                                                                                                                                                                                                                             |
      | Cities of Portugal    | draft     | Josse Malhoa | 2019-07-10 17:36 +0200 | Clash of jonnys | Lorem Ipsum is therefore                                                                                                                                                                                                                                                                                                                                                            |
      | Products of Italy     | validated | Quim Roscas  | 2019-08-14 17:36 +0200 | Sarah desert    | "<p>Watch now</p><p>{""preview_thumbnail"":""/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/dQw4w9WgXcQ.jpg?itok=s9z0clFM"",""video_url"":""https://www.youtube.com/watch?v=dQw4w9WgXcQ"",""settings"":{""responsive"":true,""width"":""854"",""height"":""480"",""autoplay"":false},""settings_summary"":[""Embedded Video (Responsive).""]}</p>" |
      | Cities of Italy       | validated | Quim Roscas  | 2020-01-01 17:36 +0200 | Clash of jonnys | <p>Download our report <a data-entity-type="file" data-entity-uuid="fb6d58c0-4618-4f18-ba8c-31dc9edbd597" href="/sites/default/files/inline-files/report-2021-09-16.xlsx">report-2021-09-16.xls</a> for more information.</p>                                                                                                                                                       |
      | Products of German    | proposed  | Quim Roscas  | 2020-01-14 17:36 +0200 | Sarah desert    | Sum is therefore                                                                                                                                                                                                                                                                                                                                                                    |
    And news content:
      | title                         | headline                    | collection      | topic                                      | state            | created              | changed              | published_at         | body                                                                                                                                                                                                                                                                                                                                                                                                                                |
      | Current biodiversity adapt    | Adapt habitats              | Clash of jonnys | Finance in EU, Supplier exchange, E-health | validated        | 2021-04-30T19:09:00Z | 2021-04-30T19:09:00Z | 2021-04-30T19:09:00Z | Here we combine global <a href="#">maps</a> of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by indigenous peoples.                                                                                                                                                                |
      | Environmental tests           | Test practices              | Nature area     | Employment and Support Allowance           | validated        | 2021-01-27T16:12:00Z | 2021-01-27T16:12:00Z | 2021-01-27T16:12:00Z | With rare exceptions current biodiversity losses are caused not by human conversion or degradation of untouched ecosystems but rather by the <em>appropriation colonization and intensification of use in lands inhabited and used by prior societies</em>.                                                                                                                                                                         |
      | Spatial construction          | Win of wildlands            | Clash of jonnys | HR, Statistics and Analysis, E-justice     | validated        | 2021-02-26T13:15:00Z | 2021-02-26T13:15:00Z | 2021-02-26T13:15:00Z | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                                                                                                                                                                                                                           |
      | Easier transformations        | Ecosystem                   | Clash of jonnys | EU and European Policies                   | validated        | 2021-01-09T10:18:00Z | 2021-01-09T10:18:00Z | 2021-01-09T10:18:00Z | Archaeological evidence shows that by 10000 BCE all societies employed <strong>ecologically transformative land use practices</strong> including burning hunting species propagation domestication cultivation have left long-term legacies across the biosphere.                                                                                                                                                                   |
      | Magnetosphere boundary        | Gas shock                   | Nature area     | Finance in EU                              | draft            | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | Gas shock                                                                                                                                                                                                                                                                                                                                                                                                                           |
      | Ambient magnetized medium     | Dust depletion level        | Clash of jonnys | Supplier exchange, E-health                | needs update     | 2021-03-19T10:18:00Z | 2021-03-19T10:18:00Z | 2021-03-19T10:18:00Z | Dust depletion level                                                                                                                                                                                                                                                                                                                                                                                                                |
      | Stellar wind charged particle | Follow spiral paths         | Nature area     | Finance in EU,  E-health                   | proposed         | 2021-05-29T10:18:00Z | 2021-05-29T10:18:00Z | 2021-05-29T10:18:00Z | Follow spiral paths                                                                                                                                                                                                                                                                                                                                                                                                                 |
      | Super-Alfvenic plasma flow    | Magnetic draping            | Sunset ball     | Finance in EU, Supplier exchange, E-health | validated        | 2021-04-29T10:18:00Z | 2021-04-29T10:18:00Z | 2021-04-29T10:18:00Z | Magnetic <a href="https://www.google.com/search?tbm=isch&q=draping">draping</a>                                                                                                                                                                                                                                                                                                                                                     |
      | Massive lensing galaxy        | Sub-millimeter sky          | Clash of jonnys | Employment and Support Allowance           | deletion request | 2021-06-29T10:18:00Z | 2021-06-29T10:18:00Z | 2021-06-29T10:18:00Z | Sub-millimeter sky                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | Planck's dusty gems           | An Einstein Ring            | Clash of jonnys | Employment and Support Allowance           | draft            | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | An Einstein Ring                                                                                                                                                                                                                                                                                                                                                                                                                    |
      | The optical morphology        | Turbulent gas fragmentation | Sunset ball     | Finance in EU, Supplier exchange, E-health | needs update     | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | Turbulent gas fragmentation                                                                                                                                                                                                                                                                                                                                                                                                         |
      | The Tarantula massive binary  | SB2 orbital solution        | Sunset ball     | E-health                                   | proposed         | 2021-02-29T10:18:00Z | 2021-02-29T10:18:00Z | 2021-02-29T10:18:00Z | SB2 orbital solution                                                                                                                                                                                                                                                                                                                                                                                                                |
      | H-rich Wolf-Rayet star        | Polarimetric analysis       | Clash of jonnys | E-health                                   | validated        | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | "<p>Polarimetric analysis</p><p>{""preview_thumbnail"":""/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/ABC12345.jpg?itok=k2SC2Gdd"",""video_url"":""https://ec.europa.eu/avservices/video/player.cfm?ref=ABC12345"",""settings"":{""responsive"":true,""width"":""854"",""height"":""480"",""autoplay"":false},""settings_summary"":[""Embedded Video (Responsive).""]}</p><p>Watch our video</p> |
      | Quasi-homogeneous evolution   | Mass-transfer was avoided   | Sunset ball     | Finance in EU, Supplier exchange, E-health | deletion request | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | Mass-transfer was avoided                                                                                                                                                                                                                                                                                                                                                                                                           |
    And event content:
      | title                       | body                                                                                                                                                                                                                                                                                                                                                                | collection      | solution              | state     | created              | changed              | published_at         |
      | Thick-target news           | Evaporation                                                                                                                                                                                                                                                                                                                                                         | Clash of jonnys | Lantern International | validated | 2021-04-26T19:05:00Z | 2021-04-26T19:05:00Z | 2021-04-26T19:05:00Z |
      | Conductive cooling losses   | Single-loop                                                                                                                                                                                                                                                                                                                                                         | Sunset ball     | Proton Global         | draft     | 2021-01-27T16:12:00Z | 2021-01-27T16:12:00Z | 2021-01-27T16:12:00Z |
      | Source of SXR plasma supply | Fast electrons                                                                                                                                                                                                                                                                                                                                                      | Sunset ball     | Shiny Shan            | validated | 2021-02-26T13:15:00Z | 2021-02-26T13:15:00Z | 2021-02-26T13:15:00Z |
      | Stars material              | Colossal material! Read our <a href="/">greypaper</a>.                                                                                                                                                                                                                                                                                                              | Clash of jonnys | Shiny Shan            | validated | 2021-01-09T10:18:00Z | 2021-01-09T10:18:00Z | 2021-01-09T10:18:00Z |
      | New event example           | Evaporation                                                                                                                                                                                                                                                                                                                                                         | Nature area     | Lantern International | draft     | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z |
      | Cooling losses              | Single-loop                                                                                                                                                                                                                                                                                                                                                         | Clash of jonnys | Proton Global         | validated | 2021-03-19T10:18:00Z | 2021-03-19T10:18:00Z | 2021-03-19T10:18:00Z |
      | SXR plasma supply           | "<p>{""preview_thumbnail"":""/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/dQw4w9WgXcQ.jpg?itok=6Lu0nqHZ"",""video_url"":""https://www.youtube.com/watch?v=dQw4w9WgXcQ"",""settings"":{""responsive"":true,""width"":""854"",""height"":""480"",""autoplay"":false},""settings_summary"":[""Embedded Video (Responsive).""]}</p>" | Clash of jonnys | Shiny Shan            | validated | 2021-05-29T10:18:00Z | 2021-05-29T10:18:00Z | 2021-05-29T10:18:00Z |
      | Dark material               | The presence of a relatively large magnetic field is acting as a <em>quenching agent</em>. The authors say the observations confirm their hypothesis that there is a strong active magnetic field interacting with the galaxy. What does all this mean for astrophysicists working on the origin of light?                                                          | Sunset ball     | Shiny Shan            | validated | 2021-04-29T10:18:00Z | 2021-04-29T10:18:00Z | 2021-04-29T10:18:00Z |
      | Tic tac toe                 | Evaporation                                                                                                                                                                                                                                                                                                                                                         | Clash of jonnys | Lantern International | proposed  | 2021-06-29T10:18:00Z | 2021-06-29T10:18:00Z | 2021-06-29T10:18:00Z |
      | Cooling wins                | Single-loop                                                                                                                                                                                                                                                                                                                                                         | Nature area     | Proton Global         | draft     | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z | 2021-03-29T10:18:00Z |
      | Plasma supply               | Fast electrons                                                                                                                                                                                                                                                                                                                                                      | Clash of jonnys | Shiny Shan            | validated | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z | 2021-01-29T10:18:00Z |
      | New material                | Colossal material                                                                                                                                                                                                                                                                                                                                                   | Nature area     | Shiny Shan            | proposed  | 2021-02-29T10:18:00Z | 2021-02-29T10:18:00Z | 2021-02-29T10:18:00Z |

    When I am on the homepage
    Then the explore section should contain the following content:
      | type       | title                       | date                   | description                                                                                                                                                                                                                             |
      | solution   | Cities of Italy             | 2020-01-01 17:36 +0200 | Download our report report-2021-09-16.xls for more information.                                                                                                                                                                         |
      | solution   | Products of Italy           | 2019-08-14 17:36 +0200 | Watch now                                                                                                                                                                                                                               |
      | solution   | Proton Global               | 2019-07-05 10:00 +0100 | An inlined image:Text after image.                                                                                                                                                                                                      |
      | solution   | Lantern International       | 2018-12-18 08:00 +0100 | Lantern International Group has started deploying personnel in focused areas. In this quest we have decided to consolidate our private businesses into one entity as a significant step towards realizing our company's long-term plan… |
      | solution   | Cities of France            | 2018-12-14 17:36 +0200 | There are many variations                                                                                                                                                                                                               |
      | solution   | Cities of Bulgaria          | 2018-11-14 17:36 +0200 | Supports fields                                                                                                                                                                                                                         |
      | solution   | Products of Bulgaria        | 2018-08-14 17:36 +0200 | Supports health                                                                                                                                                                                                                         |
      | collection | Clash of vettel             | 2020-10-18 08:00 +0100 | The point is that we do have the rights to one of our films. So we could be dealing with a film that we'd normally have to show only for three or four weeks and see what happens. But it's more like a TV show. It's bigger and has…   |
      | collection | Sarah desert                | 2020-06-18 08:00 +0100 | Watch our video! It's embedded!                                                                                                                                                                                                         |
      | collection | Clash of jonnys             | 2019-12-18 08:00 +0100 | Supports health-related fields                                                                                                                                                                                                          |
      | collection | Sunset ball                 | 2019-04-18 08:00 +0100 | There are many variations                                                                                                                                                                                                               |
      | collection | Sunset stream               | 2019-02-18 08:00 +0100 | There are many passages                                                                                                                                                                                                                 |
      | collection | Jupiter sun                 | 2018-12-18 08:00 +0100 | Sum is therefore                                                                                                                                                                                                                        |
      | collection | Clash of alex               | 2018-11-18 08:00 +0100 | Lorem Ipsum is simply dummy                                                                                                                                                                                                             |
      | collection | Nature area                 | 2018-07-18 08:00 +0100 | Supports health                                                                                                                                                                                                                         |
      | news       | Current biodiversity adapt  | 2021-04-30T19:09:00Z   | Here we combine global maps of human populations and land use over the past 12000 y with current biodiversity data to show that nearly three quarters of nature has long been shaped by histories of human habitation and use by…       |
      | news       | Super-Alfvenic plasma flow  | 2021-04-29T10:18:00Z   | Magnetic draping                                                                                                                                                                                                                        |
      | news       | H-rich Wolf-Rayet star      | 2021-03-29T10:18:00Z   | Polarimetric analysis Watch our video                                                                                                                                                                                                   |
      | news       | Spatial construction        | 2021-02-26T13:15:00Z   | Global land use history confirms that empowering the environmental stewardship of Indigenous peoples and local communities will be critical to conserving biodiversity across the planet.                                               |
      | news       | Environmental tests         | 2021-01-27T16:12:00Z   | With rare exceptions current biodiversity losses are caused not by human conversion or degradation of untouched ecosystems but rather by the appropriation colonization and intensification of use in lands inhabited and used by…      |
      | news       | Easier transformations      | 2021-01-09T10:18:00Z   | Archaeological evidence shows that by 10000 BCE all societies employed ecologically transformative land use practices including burning hunting species propagation domestication cultivation have left long-term legacies across the…  |
      | event      | SXR plasma supply           | 2021-05-29T10:18:00Z   |                                                                                                                                                                                                                                         |
      | event      | Dark material               | 2021-04-29T10:18:00Z   | The presence of a relatively large magnetic field is acting as a quenching agent. The authors say the observations confirm their hypothesis that there is a strong active magnetic field interacting with the galaxy. What does all…    |
      | event      | Thick-target news           | 2021-04-26T19:05:00Z   | Evaporation                                                                                                                                                                                                                             |
      | event      | Cooling losses              | 2021-03-19T10:18:00Z   | Single-loop                                                                                                                                                                                                                             |
      | event      | Source of SXR plasma supply | 2021-02-26T13:15:00Z   | Fast electrons                                                                                                                                                                                                                          |
      | event      | Plasma supply               | 2021-01-29T10:18:00Z   | Fast electrons                                                                                                                                                                                                                          |
      | event      | Stars material              | 2021-01-09T10:18:00Z   | Colossal material! Read our greypaper.                                                                                                                                                                                                  |

    And I should see the button "Solutions" in the "Explore block"
    And I should see the button "Collections" in the "Explore block"
    And I should see the button "News" in the "Explore block"
    And I should see the button "Events" in the "Explore block"
    And I should see the link "See more solutions" in the "Explore block"
    And I should see the link "See more collections" in the "Explore block"
    And I should see the link "See more news" in the "Explore block"
    And I should see the link "See more events" in the "Explore block"

    And the page should be cacheable
    And the page should not be cached
    When I reload the page
    Then the page should be cached

    When I click "See more solutions"
    Then I should be on the advanced search page
    And the "Solutions" content checkbox item should be selected

    When I am on the homepage
    And I click "See more collections"
    Then I should be on the advanced search page
    And the "Collections" content checkbox item should be selected

    When I am on the homepage
    And I click "See more news"
    Then I should be on the advanced search page
    And the "News" content checkbox item should be selected

    When I am on the homepage
    And I click "See more events"
    Then I should be on the advanced search page
    And the "Events" content checkbox item should be selected
