@api @terms
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
      | title                        | description                           | logo     | banner     | state     | owner         | contact information | solution type     | policy domain |
      | Information sharing protocol | Handling information sharing securely | logo.png | banner.jpg | validated | Kostas Agathe | Placebo             | [ABB169] Business | E-inclusion   |
      | Security audit tools         | Automated test of security            | logo.png | banner.jpg | validated | Kostas Agathe | Placebo             | [ABB169] Business | E-inclusion   |
    And the following releases:
      | title             | release number | creation date     | release notes                               | is version of                | state     | spatial coverage |
      | IS protocol paper | 1              | 2018-10-04 8:01am | First stable version.                       | Information sharing protocol | validated | Belgium          |
      | Fireproof         | 0.1            | 2018-10-04 8:06am | First release for the firewall bypass tool. | Security audit tools         | validated |                  |
    And the following distributions:
      | title           | description                                        | creation date     | access url       | solution                     | parent                       | downloads |
      | PDF version     | Pdf version of the paper.                          | 2018-10-04 8:07am | text.pdf         | Information sharing protocol | IS protocol paper            | 589       |
      | ZIP version     | Zip version of the paper.                          | 2018-10-04 8:04am | test.zip         | Information sharing protocol | IS protocol paper            | 514       |
      # One distribution directly attached to the "Information sharing protocol" solution.
      | Protocol draft  | Initial draft of the protocol.                     | 2018-10-04 7:59am | http://a.b.c.pdf | Information sharing protocol | Information sharing protocol | 564       |
      | Source code     | Source code for the Fireproof tool.                | 2018-10-04 8:03am | test.zip         | Security audit tools         | Fireproof                    | 432       |
      # One distribution directly attached to the "Security audit tools" solution.
      | Code of conduct | Code of conduct for contributing to this software. | 2018-10-04 8:14am | http://a.b/c.zip | Security audit tools         | Security audit tools         | 740       |
    And news content:
      | title               | body                                | created           | policy domain           | spatial coverage | solution                     | state     |
      | IS protocol meet-up | Discussion about the next standard. | 2018-10-04 8:02am | Statistics and Analysis | European Union   | Information sharing protocol | validated |
    And document content:
      | title               | document type | short title | created           | body                    | spatial coverage | policy domain | solution                     | state     |
      | IS protocol draft 2 | Document      | IS draft 2  | 2018-10-04 8:08am | Next proposition draft. | European Union   | E-inclusion   | Information sharing protocol | validated |

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
      | IS protocol paper 1  |
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
    And I should see the text "1667"

    # Test the filtering on the content type facet.
    When I click the Distribution content tab
    Then I should see the "Protocol draft" tile
    But I should not see the "IS protocol paper 1" tile

    # Reset the content type facet by clicking it again.
    When I click the Distribution content tab
    # Test the policy domain and spatial coverage inline facets.
    Then "all policy domains" should be selected in the "solution policy domain" inline facet
    And the "solution policy domain" inline facet should allow selecting the following values "E-inclusion (2), Statistics and Analysis (1)"
    And "everywhere" should be selected in the "solution spatial coverage" inline facet
    And the "solution spatial coverage" inline facet should allow selecting the following values "European Union (2), Belgium (1)"

    # Verify that the other solution is showing its related content.
    When I go to the homepage of the "Security audit tools" solution
    Then I should see the "Fireproof 0.1" tile
    #Then I should see the text "Fireproof 0.1"
    And I should see the "Code of conduct" tile
    But I should not see the "IS protocol paper 1" tile
    And I should not see the "Protocol draft" tile
    # The total downloads of the 2 distributions should be shown.
    And I should see the text "1172"

  Scenario: Forward search facets to the search page (Advanced search)
    When I go to the homepage of the "Information sharing protocol" solution
    When I click the Document content tab
    And I click "E-inclusion" in the "solution policy domain" inline facet
    And I click "European Union" in the "solution spatial coverage" inline facet
    And I click "Advanced search"
    Then I should be on the search page
    And the Document content tab should be selected
    And "Information sharing protocol (1)" should be selected in the "from" inline facet
    And "E-inclusion (1)" should be selected in the "policy domain" inline facet
    And "European Union (1)" should be selected in the "spatial coverage" inline facet
    And I should see the "IS protocol draft 2" tile
    But I should not see the "IS protocol paper 1" tile
    And I should not see the "Protocol draft" tile
    And I should not see the "PDF version" tile
    And I should not see the "ZIP version" tile
    And I should not see the "Fireproof" tile
    And I should not see the "Code of conduct" tile
    And I should not see the "Information sharing protocol" tile

  # This is a regression test for the entities that include a hashmark on their Uri.
  # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3225
  Scenario: Regression test for Uris that include a '#'.
    Given the following solution:
      | uri         | http://solution/example1/test#        |
      | title       | Information sharing protocols         |
      | description | Handling information sharing securely |
      | logo        | logo.png                              |
      | banner      | banner.jpg                            |
      | state       | validated                             |
    When I go to the homepage of the "Information sharing protocols" solution
    Then I should see the heading "Information sharing protocols"
    And I should not see the text "Page not found"

  # Regression test to ensure that related community content does not appear in the draft view.
  # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3262
  Scenario: The related content should not be shown in the draft view version as part of the content.
    When I am logged in as a facilitator of the "Information sharing protocol" solution
    And I go to the homepage of the "Information sharing protocol" solution
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Information sharing non paper"
    And I press "Save as draft"
    And I click "View draft" in the "Entity actions" region
    Then I should not see the "IS protocol paper 1" tile
    And I should not see the "Protocol draft" tile

  @terms
  Scenario: Custom pages should not be visible on the solution homepage
    Given the following solution:
      | title             | Jira restarters                      |
      | description       | Rebooting solves all issues          |
      | documentation     | text.pdf                             |
      | elibrary creation | registered users                     |
      | landing page      | http://foo-example.com/landing       |
      | webdav creation   | no                                   |
      | webdav url        | http://joinup.eu/solution/foo/webdav |
      | wiki              | http://example.wiki/foobar/wiki      |
      | state             | validated                            |
    And news content:
      | title                             | body                             | solution        | policy domain           | spatial coverage | state     |
      | Jira will be down for maintenance | As always, during business hours | Jira restarters | Statistics and Analysis | Luxembourg       | validated |
    And custom_page content:
      | title            | body                                       | solution        |
      | Maintenance page | Jira is re-indexing. Go and drink a coffee | Jira restarters |
    When I go to the homepage of the "Jira restarters" solution
    Then I should see the "Jira will be down for maintenance" tile
    And I should not see the "Maintenance page" tile

  Scenario: A link to the first collection a solution is affiliated to should be shown in the solution header.
    Given the following solutions:
      | title       | state     |
      | Robotic arm | validated |
      | ARM9        | validated |
    And collections:
      | title              | affiliates        | state     |
      | Disappointed Steel | Robotic arm, ARM9 | validated |
      | Random Arm         | ARM9              | validated |

    When I go to the homepage of the "Robotic arm" solution
    Then I should see the link "Disappointed Steel"

    When I go to the homepage of the "ARM9" solution
    Then I should see the link "Disappointed Steel"
    But I should not see the link "Random Arm"
