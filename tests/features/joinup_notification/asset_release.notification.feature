@api @wip
Feature: Asset release notification system
  In order to manage releases
  As a user of the website
  I need to be able to receive notification on changes.

  Background:
    Given user:
      | Username | Copernicus             |
      | E-mail   | copernicus@example.com |
    And the following owner:
      | name           |
      | Awesome person |
    And the following contact:
      | name        | Awesome contact            |
      | email       | awesomecontact@example.com |
      | Website URL | http://example.com         |
    And the following solution:
      | title               | My awesome solution abc |
      | description         | My awesome solution     |
      | documentation       | text.pdf                |
      | owner               | Awesome person          |
      | contact information | Awesome contact         |
      | state               | validated               |
    And the following solution user membership:
      | solution                | user       | roles |
      | My awesome solution abc | Copernicus | owner |

  Scenario: Publish a release as a facilitator.
    When I am logged in as "Copernicus"
    And I go to the homepage of the "My awesome solution abc" solution
    And I click "Add release"
    And I fill in the following:
      | Name           | My awesome release abc        |
      | Release number | 1                             |
      | Release notes  | A new amazing release is out. |
    And I press "Publish"
    Then 1 e-mail should have been sent
    And the following email should have been sent:
      | template  | Message to solution facilitators when a release is updated                                                                                       |
      | recipient | Copernicus                                                                                                                                       |
      | subject   | Joinup: The release "1" of your solution "My awesome solution abc" was successfully updated                                                      |
      | body      | Dear Copernicus, Your release "1" for the "My awesome solution abc" solution was uploaded succesfully. Kind regards, The Joinup Support Team. |

    # Update an existing release.
    When all e-mails have been sent
    And I go to the homepage of the "My awesome release abc" release
    And I click "Edit" in the "Entity actions" region
    And I fill in "Release number" with "v2"
    And I press "Update"
    Then 1 e-mail should have been sent
    And the following email should have been sent:
      | template  | Message to solution facilitators when a release is updated                                                                                        |
      | recipient | Copernicus                                                                                                                                        |
      | subject   | Joinup: The release "v2" of your solution "My awesome solution abc" was successfully updated                                                      |
      | body      | Dear Copernicus, Your release "v2" for the "My awesome solution abc" solution was uploaded succesfully. Kind regards, The Joinup Support Team. |

    # Debug step.
    And I delete the "My awesome release abc" release
