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
      | title          | "Winter of 95" |
      | release number | 6.1            |
      | is version of  | OpenBSD        |
      | state          | validated      |
    And distributions:
      | title          | description                 | parent         | access url                                                  | licence          |
      | i386           | The i386 version            | "Winter of 95" | https://mirrors.evowise.com/pub/OpenBSD/6.1/i386/random.iso | Postcard licence |
      | Changelog      | Detailed Changelog          | "Winter of 95" | text.pdf                                                    | Postcard licence |
      | OpenDSB images | Images for logos and flyers | OpenBSD        | test.zip                                                    | Postcard licence |
    And user:
      | Username | Bradley Emmett             |
      | E-mail   | bradley.emmett@example.com |

    When I am logged in as "Bradley Emmett"
    And I go to the "Berkeley Software Distributions" collection
    And I click "OpenBSD"
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    And the "i386" asset distribution should not have any download urls
    And I should see the download link in the "OpenDSB images" asset distribution
    And I should see the download link in the "Changelog" asset distribution

    Then I click "Download" in the "OpenDSB images" asset distribution
    And I click "Download" in the "Changelog" asset distribution

    When I am an anonymous user
    And I go to the "OpenBSD" solution
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    # The same download links are shown to anonymous users.
    And the "i386" asset distribution should not have any download urls
    And I should see the download link in the "OpenDSB images" asset distribution
    And I should see the download link in the "Changelog" asset distribution

    # Anonymous users will be prompted with a modal to enter their e-mails.
    When I click "Download" in the "OpenDSB images" asset distribution
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

    When I am logged in as a user with the moderator role
    Then I click "Manage"
    And I click "Distribution downloads"
    Then I should see the following download entries:
      | user                     | e-mail                     | file name | distribution   |
      | Anonymous (not verified) | trackme@example.com        | test.zip  | OpenDSB images |
      | Bradley Emmett           | bradley.emmett@example.com | text.pdf  | Changelog      |
      | Bradley Emmett           | bradley.emmett@example.com | test.zip  | OpenDSB images |
