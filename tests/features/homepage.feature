@api
Feature: Homepage
  In order to present a good introduction of the website to a new visitor
  As a product owner
  I want to highlight the most important sections on the homepage

  @commitSearchIndex
  Scenario: Statistics about important content types are shown to anonymous users
    Given I am not logged in
    And I am on the homepage
    # At the very start of the test there is no content yet.
    Then I should see the following statistics:
      | Solutions   | 0 |
      | Collections | 0 |
      | Content     | 0 |
    # Test that the page is successfully cached.
    When I reload the page
    Then the page should be cached

    Given the following collections:
      | title               | state            |
      | Political sciences  | draft            |
      | Forms of government | proposed         |
      | Social classes      | validated        |
      | Elections           | archival request |
      | Party structure     | archived         |
    And the following solutions:
      | title             | state        | collection     |
      | Economic theory   | draft        | Social classes |
      | Economic history  | proposed     | Social classes |
      | Laws of economics | validated    | Social classes |
      | Planned economy   | needs update | Social classes |
      | Economic growth   | blacklisted  | Social classes |
    And custom_page content:
      | title                | collection     |
      | Developing economics | Social classes |
    And discussion content:
      | title                         | state        | collection     |
      | Prosperity economics          | needs update | Social classes |
      | Cost-benefit analysis         | proposed     | Social classes |
      | Economic systems              | validated    | Social classes |
      | Socialist schools before Marx | archived     | Social classes |
    And document content:
      | title               | state     | collection     |
      | Socialist economics | validated | Social classes |
    And event content:
      | title                         | state        | collection     |
      | Trotskism                     | draft        | Social classes |
      | Corporative economic theories | validated    | Social classes |
      | Social economics              | needs update | Social classes |
      | Labour theory                 | proposed     | Social classes |
    And news content:
      | title                | state            | collection     |
      | Regional economy     | draft            | Social classes |
      | World economy        | proposed         | Social classes |
      | Economic cooperation | validated        | Social classes |
      | Economic dynamics    | deletion request | Social classes |
      | Economic cycles      | needs update     | Social classes |

    # Only statistics of publicly visible content should be counted.
    When I reload the page
    Then I should see the following statistics:
      | Solutions   | 1 |
      | Collections | 3 |
      | Content     | 5 |
    # The cache should have been cleared when new content is created.
    And the page should not be cached
    # The page should still be cacheable.
    When I reload the page
    Then the page should be cached

    # The search page cache is not invalidated correctly and shows stale
    # results. This will be fixed in ISAICP-3428. Remove this workaround when
    # working on that issue.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3428
    Given the cache has been cleared

    # Check if the "Solutions" link leads to the pre-filtered search results.
    # This shows solutions in the 'validated' state.
    When I click "Solutions" in the "Header" region
    Then I should see the heading "Solutions"
    And I should see the following lines of text:
      | Laws of economics |
    But I should not see the following lines of text:
      | Political sciences            |
      | Forms of government           |
      | Social classes                |
      | Elections                     |
      | Party structure               |
      | Economic theory               |
      | Economic history              |
      | Planned economy               |
      | Economic growth               |
      | Developing economics          |
      | Prosperity economics          |
      | Cost-benefit analysis         |
      | Economic systems              |
      | Socialist schools before Marx |
      | title                         |
      | Socialist economics           |
      | title                         |
      | Trotskism                     |
      | Corporative economic theories |
      | Social economics              |
      | Labour theory                 |
      | Regional economy              |
      | World economy                 |
      | Economic cooperation          |
      | Economic dynamics             |
      | Economic cycles               |

    # Check if the "Collections" link leads to the pre-filtered search results.
    # This shows collections in the "validated' state.
    # 'archival request', and 'archived'.
    When I go to the homepage
    And I click "Collections" in the "Header" region
    Then I should see the heading "Collections"
    And I should see the following lines of text:
      | Social classes  |
      | Elections       |
      | Party structure |

    But I should not see the following lines of text:
      | Political sciences            |
      | Forms of government           |
      | Economic theory               |
      | Economic history              |
      | Laws of economics             |
      | Planned economy               |
      | Economic growth               |
      | Developing economics          |
      | Prosperity economics          |
      | Cost-benefit analysis         |
      | Economic systems              |
      | Socialist schools before Marx |
      | title                         |
      | Socialist economics           |
      | title                         |
      | Trotskism                     |
      | Corporative economic theories |
      | Social economics              |
      | Labour theory                 |
      | Regional economy              |
      | World economy                 |
      | Economic cooperation          |
      | Economic dynamics             |
      | Economic cycles               |

    # Check if the "Content" link leads to the pre-filtered search results.
    # This shows community content in the states 'validated' and 'archived'.
    When I go to the homepage
    And I click "Events, discussions, news ..." in the "Header" region
    Then I should see the heading "Keep up to date"
    And I should see the following lines of text:
      | Economic systems              |
      | Socialist schools before Marx |
      | Socialist economics           |
      | Corporative economic theories |
      | Economic cooperation          |
    But I should not see the following lines of text:
      | Political sciences    |
      | Forms of government   |
      | Social classes        |
      | Elections             |
      | Party structure       |
      | Economic theory       |
      | Economic history      |
      | Laws of economics     |
      | Planned economy       |
      | Economic growth       |
      | Developing economics  |
      | Prosperity economics  |
      | Cost-benefit analysis |
      | Trotskism             |
      | Social economics      |
      | Labour theory         |
      | Regional economy      |
      | World economy         |
      | Economic dynamics     |
      | Economic cycles       |

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
