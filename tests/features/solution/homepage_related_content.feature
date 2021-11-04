@api @terms @group-g
Feature: Solution homepage
  In order find content around a topic
  As a user of the website
  I need to be able see all content related to a solution on the solution homepage

  Background:
    Given the following owner:
      | name          | type                  |
      | Kostas Agathe | Private Individual(s) |
    And the following contact:
      | name  | Placebo             |
      | email | Placebo@example.com |
    And the following solutions:
      | title                        | description                           | logo     | banner     | state     | owner         | contact information | solution type | topic       |
      | Information sharing protocol | Handling information sharing securely | logo.png | banner.jpg | validated | Kostas Agathe | Placebo             | Business      | E-inclusion |
      | Security audit tools         | Automated test of security            | logo.png | banner.jpg | validated | Kostas Agathe | Placebo             | Business      | E-inclusion |
    And the following releases:
      | title             | release number | creation date     | release notes                               | is version of                | state     | spatial coverage |
      | IS protocol paper | 1              | 2018-10-04 8:01am | First stable version.                       | Information sharing protocol | validated | Belgium          |
      | Fireproof         | 0.1            | 2018-10-04 8:06am | First release for the firewall bypass tool. | Security audit tools         | validated |                  |
    And the following distributions:
      | title           | description                                        | creation date     | access url       | parent                       | downloads |
      | PDF version     | Pdf version of the paper.                          | 2018-10-04 8:07am | text.pdf         | IS protocol paper            | 589       |
      | ZIP version     | Zip version of the paper.                          | 2018-10-04 8:04am | test.zip         | IS protocol paper            | 514       |
      # One distribution directly attached to the "Information sharing protocol" solution.
      | Protocol draft  | Initial draft of the protocol.                     | 2018-10-04 7:59am | http://a.b.c.pdf | Information sharing protocol | 564       |
      | Source code     | Source code for the Fireproof tool.                | 2018-10-04 8:03am | test.zip         | Fireproof                    | 432       |
      # One distribution directly attached to the "Security audit tools" solution.
      | Code of conduct | Code of conduct for contributing to this software. | 2018-10-04 8:14am | http://a.b/c.zip | Security audit tools         | 740       |
    And news content:
      | title               | body                                | created           | topic                   | spatial coverage | solution                     | state     |
      | IS protocol meet-up | Discussion about the next standard. | 2018-10-04 8:02am | Statistics and Analysis | European Union   | Information sharing protocol | validated |
    And document content:
      | title               | document type | short title | created           | body                    | spatial coverage | topic       | solution                     | state     |
      | IS protocol draft 2 | Document      | IS draft 2  | 2018-10-04 8:08am | Next proposition draft. | European Union   | E-inclusion | Information sharing protocol | validated |

  @clearStaticCache
  Scenario: The solution homepage shows related content.
    When I go to the homepage of the "Information sharing protocol" solution
    # I should see only the related release.
    # And the distribution directly associated.
    Then I should see the following tiles in the correct order:
      # Created in 8:08am.
      | IS protocol draft 2  |
      # Created in 8:02am.
      | IS protocol meet-up  |
      # Created in 8:01am.
      | IS protocol paper    |
      # Created in 7:59am.
      | Protocol draft       |
      # The related solutions is shown in a block later in the page.
      | Security audit tools |

    # Distribution associated to a release should not be shown.
    But I should not see the "PDF version" tile
    And I should not see the "ZIP version" tile
    # Unrelated content should not be shown.
    And I should not see the "Fireproof" tile
    And I should not see the "Code of conduct" tile
    # Nor the solution itself should be shown.
    And I should not see the "Information sharing protocol" tile
    # The total downloads of the 3 distributions should be shown.
    And I should see the text "Downloads: 1667"

    # Test that the solution download counter is updating.
    Given the download count of "PDF version" is 1589
    When I reload the page
    Then I should not see the text "Downloads: 1667"
    But I should see the text "Downloads: 2667"
    # Reset all compounded distribution download counters to 0.
    Given the download count of "PDF version" is 0
    And the download count of "ZIP version" is 0
    And the download count of "Protocol draft" is 0
    When I reload the page
    Then I should not see the text "Downloads: 2667"
    # Restore counters to their original values.
    Given the download count of "PDF version" is 589
    And the download count of "ZIP version" is 514
    And the download count of "Protocol draft" is 564
    When I reload the page
    Then I should see the text "Downloads: 1667"

    # Test the filtering on the content type facet.
    When I click the Distribution content tab
    Then I should see the "Protocol draft" tile
    But I should not see the "IS protocol paper 1" tile

    # Reset the content type facet by clicking it again.
    When I click the Distribution content tab
    # Test the topic and spatial coverage inline facets.
    Then "all topics" should be selected in the "solution topic" inline facet
    And the "solution topic" inline facet should allow selecting the following values:
      | E-inclusion (2)             |
      | Statistics and Analysis (1) |
    And "everywhere" should be selected in the "solution spatial coverage" inline facet
    And the "solution spatial coverage" inline facet should allow selecting the following values:
      | European Union (2) |
      | Belgium (1)        |

    # Verify that the other solution is showing its related content.
    When I go to the homepage of the "Security audit tools" solution
    Then I should see the "Fireproof" tile
    #Then I should see the text "Fireproof 0.1"
    And I should see the "Code of conduct" tile
    But I should not see the "IS protocol paper 1" tile
    And I should not see the "Protocol draft" tile
    # The total downloads of the 2 distributions should be shown.
    And I should see the text "Downloads: 1172"

  # Regression test to ensure that related community content does not appear in the draft view.
  # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3262
  Scenario: The related content should not be shown in the draft view version as part of the content.
    When I am logged in as a facilitator of the "Information sharing protocol" solution
    And I go to the homepage of the "Information sharing protocol" solution
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Information sharing non paper"
    And I press "Save as draft"
    And I click "View draft" in the "Entity actions" region
    Then I should not see the "IS protocol paper 1" tile
    And I should not see the "Protocol draft" tile
