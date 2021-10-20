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
      | title             | state            | collection     |
      | Economic theory   | draft            | Social classes |
      | Economic history  | proposed         | Social classes |
      | Laws of economics | validated        | Social classes |
      | Planned economy   | needs update     | Social classes |
      | Economic growth   | blacklisted      | Social classes |
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
    Then I should see the text "Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme. It offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions."
    When I click "Collections" in the "Header" region
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

  Scenario: the small homepage header should be shown only to logged in users.
    When I am an anonymous user
    And I go to the homepage
    Then I should not see the small header
    And the response should not contain "user-profile-icon.png"
    But I should see the link "Sign in"

    # The header should still be shown in the other pages.
    When I click "Collections"
    Then I should see the small header

    When I am logged in as a user with the "authenticated" role
    And I go to the homepage
    Then I should see the text "Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme. It offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions."
    And I should see the small header

    # Homepage should also be cacheable for logged in users.
    And the page should be cacheable

    # The header should still be shown in the other pages.
    When I click "Collections"
    Then I should see the small header

  Scenario: Only specific social network links are available in the footer.
    When I am on the homepage
    Then I should see the link "LinkedIn" in the Footer region
    And the "LinkedIn" link should point to "https://www.linkedin.com/groups/2600644/"
    And I should see the link "Twitter" in the Footer region
    But I should not see the link "Facebook" in the Footer region

  @version
  Scenario Outline: The current version of the Joinup platform is shown in the footer.
    Given the Joinup version is set to "<version>"
    When I am on the homepage
    Then I should see the link "<version>" in the Footer region
    And the "<version>" link should point to "<url>"

    Examples:
      | version                    | url                                                                |
      | v1.57.0                    | https://git.fpfis.eu/digit/digit-joinup-reference/-/tags/v1.57.0   |
      | v1.57.0-177-g0123456abcdef | https://git.fpfis.eu/digit/digit-joinup-dev/-/commit/0123456abcdef |
