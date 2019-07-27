@api
Feature: My subscriptions
  As a user I must be able to manage my subscriptions and related settings.

  Background:
    Given user:
      | Username | Auric Goldfinger  |
      | Password | oddjob            |
      | E-mail   | auric@example.com |

  Scenario: Check access to the subscription management pages
    Given user:
      | Username | Chanelle Testa    |
      | E-mail   | chate@example.com |

    # No access for anonymous users.
    Given I am an anonymous user
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the error message "Access denied. You must sign in to view this page."

    # Authenticated users can manage their own subscriptions.
    Given I am logged in as "Auric Goldfinger"
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the heading "My subscriptions"

    # Moderators can manage subscriptions of any user.
    Given I am logged in as a moderator
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the heading "Subscription settings"

    # Users cannot access subscription settings of other users.
    Given I am logged in as "Chanelle Testa"
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should get an access denied error

  @javascript
  Scenario: Manage my subscriptions
    Given collections:
      | title          | state     | abstract                                                       |
      | Alpha Centauri | validated | A triple star system at a distance of 4.3 light years.         |
      | Barnard's Star | draft     | A low mass red dwarf at around 6 light years distance.         |
      | Wolf 359       | proposed  | Wolf 359 is a red dwarf star located in the constellation Leo. |
    And the following collection user memberships:
      | collection     | user             | roles       |
      | Alpha Centauri | Auric Goldfinger | member      |
      | Barnard's Star | Auric Goldfinger | owner       |
      | Wolf 359       | Auric Goldfinger | facilitator |

    # Users that are not a member of any collections should see the empty text.
    Given I am logged in as an "authenticated user"
    # The "My subscriptions" link is present in the user menu in the top right.
    And I open the account menu
    And I click "My subscriptions"
    Then I should see the heading "My subscriptions"
    And I should see the text "No collection memberships yet. Join one or more collections to subscribe to their content!"
    And I should not see the text "Alpha Centauri"

    # Log in as a user that is a member of 3 collections. The subscriptions for
    # all 3 collections should be shown.
    Given I am logged in as "Auric Goldfinger"

    # This step is actually a shortcut for
    # When I open the account menu
    # And I click "My subscriptions"
    When I go to my subscription dashboard

    # The empty text should not be shown now.
    Then I should not see the text "No collection memberships yet."

    And the following collection content subscriptions should be selected:
      | Alpha Centauri | Discussion, Document, Event, News |
      | Barnard's Star | Discussion, Document, Event, News |
      | Wolf 359       | Discussion, Document, Event, News |

    And I should see the following lines of text:
      | A triple star system at a distance of 4.3 light years.         |
      | A low mass red dwarf at around 6 light years distance.         |
      | Wolf 359 is a red dwarf star located in the constellation Leo. |

    And the "Save changes" button on the "Alpha Centauri" subscription card should be disabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be disabled

    Given I uncheck the "Discussion" checkbox of the "Alpha Centauri" subscription
    And I uncheck the "Event" checkbox of the "Wolf 359" subscription

    And the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled

    Given I press "Save changes" on the "Alpha Centauri" subscription card
    And I wait for AJAX to finish
    Then I should not see the "Save changes" button on the "Alpha Centauri" subscription card
    But I should see the "Saved!" button on the "Alpha Centauri" subscription card

    And the "Saved!" button on the "Alpha Centauri" subscription card should be disabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    # The button remains enabled as changes persist after AJAX save.
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled

    And the following collection content subscriptions should be selected:
      | Alpha Centauri | Document, Event, News             |
      | Barnard's Star | Discussion, Document, Event, News |
      | Wolf 359       | Discussion, Document, News        |

    # Re-try a change on the same collection.
    Given I check the "Discussion" checkbox of the "Alpha Centauri" subscription
    And the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled
    Given I press "Save changes" on the "Alpha Centauri" subscription card
    And I wait for AJAX to finish
    Then I should not see the "Save changes" button on the "Alpha Centauri" subscription card
    But I should see the "Saved!" button on the "Alpha Centauri" subscription card

    # Ensure that the changes are not saved for all cards and unsaved changes are lost.
    Given I reload the page
    And the following collection content subscriptions should be selected:
      | Alpha Centauri | Discussion, Document, Event, News |
      | Barnard's Star | Discussion, Document, Event, News |
      # Even though 'Event' was unchecked, and another 'Save changes' button was clicked,
      # the changes for 'Wolf 359' were not saved and so they are reloaded.
      | Wolf 359       | Discussion, Document, Event, News |

  @javascript
  Scenario Outline: Change the notification frequency of my digests
    Given collection:
      | title | Malicious plans |
      | state | validated       |
    And discussion content:
      | title        | body                   | collection      | state     | author           |
      | Water supply | Contaminate it with GB | Malicious plans | validated | Auric Goldfinger |

    Given I am logged in as "Auric Goldfinger"
    And I open the account menu
    And I click "My subscriptions"

    Then I should see the heading "My subscriptions"
    And the following fields should be present "Notification frequency"

    # The "Save changes" button should initially be disabled, but when a change
    # is made it should become enabled so the user can save their changes. We
    # are toggling two values so we can reliably check this regardless of the
    # initial state of the button when the page is loaded.
    And the "Save changes" button should be disabled
    When I select "Daily" from "Notification frequency"
    And I select "Weekly" from "Notification frequency"
    Then the "Save changes" button should be enabled

    # When we save the changes the button label should change to "Saved!" and
    # the button should become disabled again.
    And I select "<option>" from "Notification frequency"
    When I press "Save changes"
    Then the "Saved!" button should be disabled
    And the following fields should not be present "Save changes"

    Given I am logged in as a moderator
    When I go to the discussion content "Water supply" edit screen
    And I fill in "Title" with "Contaminate it with Sarin"
    And I press "Update"
    Then the <frequency> digest for "Auric Goldfinger" should contain the following message for the "Contaminate it with Sarin" node:
      | mail_subject | Joinup: The discussion "Contaminate it with Sarin" was updated in the space of "Malicious plans" |
      | mail_body    | The discussion "Contaminate it with Sarin" was updated in the "Malicious plans" collection.      |

    Examples:
      | option  | frequency |
      | Daily   | daily     |
      | Weekly  | weekly    |
      | Monthly | monthly   |
