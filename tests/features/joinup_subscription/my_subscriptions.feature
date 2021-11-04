@api @group-c
Feature: My subscriptions
  As a user I must be able to manage my subscriptions and related settings.

  Background:
    Given user:
      | Username    | Auric Goldfinger  |
      | Password    | oddjob            |
      | E-mail      | auric@example.com |
      | First name  | Auric             |
      | Family name | Goldfinger        |

  Scenario: Check access to the subscription management pages
    Given user:
      | Username | Chanelle Testa    |
      | E-mail   | chate@example.com |

    # No access for anonymous users.
    Given I am an anonymous user
    When I go to the subscription settings of "Auric Goldfinger"
    Then I should see the heading "Sign in to continue"
    When I go to the public profile of "Auric Goldfinger"
    Then I should not see the link "Subscriptions"

    # Authenticated users can not access their own subscription settings through
    # the entity actions, only through "My subscriptions".
    Given I am logged in as "Auric Goldfinger"
    When I go to the subscription settings of "Auric Goldfinger"
    Then I should get an access denied error
    When I go to the public profile of "Auric Goldfinger"
    Then I should not see the link "Subscriptions" in the "Entity actions" region

    # Moderators can manage subscriptions of any user.
    Given I am logged in as a moderator
    When I go to the subscription settings of "Auric Goldfinger"
    Then I should see the heading "Subscription settings"
    When I go to the public profile of "Auric Goldfinger"
    Then I should see the link "Subscriptions" in the "Entity actions" region
    When I click "Subscriptions" in the "Entity actions" region
    Then I should see the heading "Subscription settings"

    # Users cannot access subscription settings of other users.
    Given I am logged in as "Chanelle Testa"
    When I go to the subscription settings of "Auric Goldfinger"
    Then I should get an access denied error
    When I go to the public profile of "Auric Goldfinger"
    Then I should not see the link "Subscriptions"

  Scenario: Check default subscription frequency for a new user
    When I am logged in as a user with the "authenticated" role
    And I click "My subscriptions"
    Then I should see the heading "My subscriptions"

    And the option with text "Weekly" from select "Notification frequency" is selected
    And the available options in the "Notification frequency" select should be "Daily, Weekly, Monthly"

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
    And I should see the link "Collections"
    And I should see the link "Solutions"
    And I should see the text "No collection memberships yet. Join one or more collections to subscribe to their content!"
    And I should see the text "No solution memberships yet. Join one or more solutions to subscribe to their content!"
    But I should not see the link "Unsubscribe from all"
    And I should not see the text "Alpha Centauri"

    # Log in as a user that is a member of 3 collections. The subscriptions for
    # all 3 collections should be shown.
    Given I am logged in as "Auric Goldfinger"

    # This step is actually a shortcut for
    # When I open the account menu
    # And I click "My subscriptions"
    When I go to my subscriptions

    # The empty text should not be shown now.
    Then I should not see the text "No collection memberships yet."
    And I should not see the link "Unsubscribe from all"

    And the following content subscriptions should be selected:
      | Alpha Centauri |  |
      | Barnard's Star |  |
      | Wolf 359       |  |

    # The collection abstracts were visible in an earlier version but were
    # removed in a more recent design update. Let's ensure they do not pop back
    # into existence.
    And I should not see the following lines of text:
      | A triple star system at a distance of 4.3 light years.         |
      | A low mass red dwarf at around 6 light years distance.         |
      | Wolf 359 is a red dwarf star located in the constellation Leo. |

    And the "Save changes" button on the "Alpha Centauri" subscription card should be disabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be disabled

    Given I check the "Discussion" checkbox of the "Alpha Centauri" subscription
    And I check the "Event" checkbox of the "Wolf 359" subscription

    And the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled

    # Tests that the button gets disabled when the checkboxes are reverted to
    # their initial state.
    When I uncheck the "Discussion" checkbox of the "Alpha Centauri" subscription
    Then the "Save changes" button on the "Alpha Centauri" subscription card should be disabled
    When I check the "Event" checkbox of the "Alpha Centauri" subscription
    Then the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    When I check the "News" checkbox of the "Alpha Centauri" subscription
    Then the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    But I uncheck the "Event" checkbox of the "Alpha Centauri" subscription
    And I uncheck the "News" checkbox of the "Alpha Centauri" subscription
    Then the "Save changes" button on the "Alpha Centauri" subscription card should be disabled

    Given I check the "Discussion" checkbox of the "Alpha Centauri" subscription
    When I press "Save changes" on the "Alpha Centauri" subscription card
    And I wait for AJAX to finish
    Then I should not see the "Save changes" button on the "Alpha Centauri" subscription card
    But I should see the "Saved!" button on the "Alpha Centauri" subscription card
    # The button "Unsubscribe from all" is now visible.
    And I should see the link "Unsubscribe from all"

    And the "Saved!" button on the "Alpha Centauri" subscription card should be disabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    # The button remains enabled as changes persist after AJAX save.
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled

    And the following content subscriptions should be selected:
      | Alpha Centauri | Discussion |
      | Barnard's Star |            |
      | Wolf 359       | Event      |

    # The 'Event' subscription was checked but not saved, so we should not be subscribed to it.
    And I should have the following content subscriptions:
      | Alpha Centauri | Discussion |
      | Barnard's Star |            |
      | Wolf 359       |            |

    # Re-try a change on the same collection.
    Given I uncheck the "Discussion" checkbox of the "Alpha Centauri" subscription
    And the "Save changes" button on the "Alpha Centauri" subscription card should be enabled
    And the "Save changes" button on the "Barnard's Star" subscription card should be disabled
    And the "Save changes" button on the "Wolf 359" subscription card should be enabled
    Given I press "Save changes" on the "Alpha Centauri" subscription card
    And I wait for AJAX to finish
    # No subscriptions actually exist even though one is selected.
    And I should not see the link "Unsubscribe from all"
    Then I should not see the "Save changes" button on the "Alpha Centauri" subscription card
    But I should see the "Saved!" button on the "Alpha Centauri" subscription card

    # Ensure that the changes are not saved for all cards and unsaved changes are lost.
    Given I reload the page
    And the following content subscriptions should be selected:
      | Alpha Centauri |  |
      | Barnard's Star |  |
      # Even though 'Event' was unchecked, and another 'Save changes' button was clicked,
      # the changes for 'Wolf 359' were not saved and so they are reloaded.
      | Wolf 359       |  |

    And I should have the following content subscriptions:
      | Alpha Centauri |  |
      | Barnard's Star |  |
      | Wolf 359       |  |

  @javascript
  Scenario Outline: Change the notification frequency of my digests
    Given collection:
      | title | Malicious plans |
      | state | validated       |
    And the following collection user memberships:
      | collection      | user             |
      | Malicious plans | Auric Goldfinger |
    And the following collection content subscriptions:
      | collection      | user             | subscriptions |
      | Malicious plans | Auric Goldfinger | discussion    |
    And discussion content:
      | title        | body                   | collection      | state | author           |
      | Water supply | Contaminate it with GB | Malicious plans | draft | Auric Goldfinger |

    Given I am logged in as "Auric Goldfinger"
    When I open the account menu
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
    When I go to the edit form of the "Water supply" discussion
    And I press "Publish"
    # @todo: a caching issue is causing the message to have empty fields when
    # rendered.
    # @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5723
    And the cache has been cleared
    Then the <frequency> group content subscription digest for "Auric Goldfinger" should match the following message:
      | Water supply |

    Examples:
      | option  | frequency |
      | Daily   | daily     |
      | Weekly  | weekly    |
      | Monthly | monthly   |
