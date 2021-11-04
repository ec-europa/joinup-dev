@api @javascript @antibot @group-f
Feature: As a visitor or logged-in user, when I want to post content, the form
  should be protected by Antibot.

  Scenario Outline: Anonymous forms.
    Given I am an anonymous user
    And I visit "<path>"
    Then the form is protected by Antibot

    Examples:
      | path           |
      | /user/password |
      | /contact       |

  Scenario: Authenticated users.
    Given users:
      | Username |
      | Günther  |
    And the following collection:
      | title | Family photos |
      | state | validated     |
    And discussion content:
      | title      | collection    | state     |
      | Let's talk | Family photos | validated |
    And event content:
      | title    | collection    | state     |
      | Birthday | Family photos | validated |
    And news content:
      | title       | collection    | state     |
      | Got married | Family photos | validated |

    Given I am logged in as "Günther"
    And I go to the homepage of the "Family photos" collection
    Then the form is protected by Antibot

    When I press "Join this collection"
    Then I should see the success message "You are now a member of Family photos."
    And a modal should open
    Then the form is protected by Antibot

    When I press "No thanks" in the "Modal buttons" region
    And I go to the homepage of the "Family photos" collection
    And I click "Add discussion" in the plus button menu
    Then the form is protected by Antibot

    When I go to the "Let's talk" discussion
    # Comment form.
    Then the form is protected by Antibot

    When I go to the "Birthday" event
    # Comment form.
    Then the form is protected by Antibot

    When I go to the "Got married" news
    # Comment form.
    Then the form is protected by Antibot

    When I click "Share"
    And I wait for AJAX to finish
    Then the form is protected by Antibot
