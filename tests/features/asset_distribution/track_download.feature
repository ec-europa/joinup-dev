@api
Feature: Asset distribution editing.
  As a privileged user of the website
  I want to track downloads of distributions
  So I can create some metrics

  @javascript
  Scenario: Downloads made by authenticated users are logged.
    Given the following licence:
      | title       | Postcard licence                     |
      | description | Send a postcard from where you live. |
      | type        | Attribution                          |
    And solution:
      | title | OpenBSD   |
      | state | validated |
    And collection:
      | title      | Berkeley Software Distributions |
      | affiliates | OpenBSD                         |
      | state      | validated                       |
    And release:
      | title          | Winter of 95 |
      | release number | 6.1          |
      | is version of  | OpenBSD      |
      | state          | validated    |
    And distributions:
      | title          | description                 | parent       | access url                                                  | licence          |
      | i386           | The i386 version            | Winter of 95 | https://mirrors.evowise.com/pub/OpenBSD/6.1/i386/random.iso | Postcard licence |
      | Changelog      | Detailed Changelog          | Winter of 95 | text.pdf                                                    | Postcard licence |
      | OpenBSD images | Images for logos and flyers | OpenBSD      | test.zip                                                    | Postcard licence |
    And users:
      | Username           | E-mail                        |
      | Bradley Emmett     | bradley.emmett@example.com    |
      | Marianne Sherburne | marianne.herburne@example.com |

    When I am logged in as "Bradley Emmett"
    And I go to the "Berkeley Software Distributions" collection
    And I click "OpenBSD"
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    And the "i386" asset distribution should not have any download urls
    And I should see the download link in the "OpenBSD images" asset distribution
    And I should see the download link in the "Changelog" asset distribution

    # Clicking these links will track the download event.
    Then I click "Download" in the "OpenBSD images" asset distribution
    And I click "Download" in the "Changelog" asset distribution

    When I am an anonymous user
    And I go to the "OpenBSD" solution
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    # The same download links are shown to anonymous users.
    And the "i386" asset distribution should not have any download urls
    And I should see the download link in the "OpenBSD images" asset distribution
    And I should see the download link in the "Changelog" asset distribution

    # Anonymous users will be prompted with a modal to enter their e-mails.
    When I click "Download" in the "OpenBSD images" asset distribution
    Then a modal should open
    And I should see the text "Download in progress"
    When I fill in "E-mail address" with "trackme@example.com" in the "Modal content" region
    Then I press "Submit" in the "Modal buttons" region
    Then the modal should be closed

    # Verify that users can opt-out from inserting their e-mail.
    When I click "Download" in the "Changelog" asset distribution
    Then a modal should open
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed

    # Verify that the modal also shows in the overview page.
    When I click "Details" in the "Changelog" asset distribution
    Then I should see the link "Download"
    When I click "Download"
    Then a modal should open
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed

    When I am logged in as a user with the moderator role
    And I go to the distribution downloads page
    Then I should see the following download entries:
      | user                     | e-mail                     | distribution   |
      | Anonymous (not verified) | trackme@example.com        | OpenBSD images |
      | Bradley Emmett           | bradley.emmett@example.com | Changelog      |
      | Bradley Emmett           | bradley.emmett@example.com | OpenBSD images |

    # Verify that the tracking happens when clicking the Download button in
    # the tile view mode and in the canonical page view of the distribution.
    When I am logged in as "Marianne Sherburne"
    And I go to the "OpenBSD" solution
    # The only download link is the one in the "OpenBSD images" tile.
    Then I click "Download"
    And I click "Winter of 95 6.1"
    # The only download link is the one in the "Changelog" tile.
    And I click "Download"

    When I am logged in as a user with the moderator role
    And I go to the distribution downloads page
    Then I should see the following download entries:
      | user                     | e-mail                        | distribution   |
      | Marianne Sherburne       | marianne.herburne@example.com | Changelog      |
      | Marianne Sherburne       | marianne.herburne@example.com | OpenBSD images |
      | Anonymous (not verified) | trackme@example.com           | OpenBSD images |
