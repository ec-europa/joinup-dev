@api
Feature: User subscription settings
  As a user I must be able to set and view my subscription settings.

  Background:
    Given user:
      | Username   | Auric Goldfinger  |
      | Password   | oddjob            |
      | E-mail     | auric@example.com |

  Scenario: Check access to the subscription management pages
    Given user:
      | Username   | Chanelle Testa    |
      | E-mail     | chate@example.com |

    # No access for anonymous users.
    Given I am an anonymous user
    When I go to the subscription settings form of "Auric Goldfinger"
    Then I should see the error message "Access denied. You must sign in to view this page."
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the error message "Access denied. You must sign in to view this page."

    # Authenticated users can manage their own subscriptions.
    Given I am logged in as "Auric Goldfinger"
    When I go to the subscription settings form of "Auric Goldfinger"
    Then I should see the heading "Subscription settings"
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the heading "Collection subscriptions"

    # Moderators can manage subscriptions of any user.
    Given I am logged in as a moderator
    When I go to the subscription settings form of "Auric Goldfinger"
    Then I should see the heading "Subscription settings"
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should see the heading "Collection subscriptions"

    # Users cannot access subscription settings of other users.
    Given I am logged in as "Chanelle Testa"
    When I go to the subscription settings form of "Auric Goldfinger"
    Then I should get an access denied error
    When I go to the subscription dashboard of "Auric Goldfinger"
    Then I should get an access denied error

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
    When I click "My subscriptions"
    Then I should see the heading "My subscriptions"
    And I should see the text "No collection memberships yet. Join one or more collections to subscribe to their content!"
    But I should not see the text "Alpha Centauri"
    And I should not see the "Submit" button

    # Log in as a user that is a member of 3 collections. The subscriptions for
    # all 3 collections should be shown.
    Given I am logged in as "Auric Goldfinger"
    When I go to my subscription dashboard

    # The empty text should not be shown now.
    Then I should not see the text "No collection memberships yet."

    # Check that the 3 collections are shown alongside the abstracts.
    But I should see the following headings:
      | Alpha Centauri |
      | Barnard's Star |
      | Wolf 359       |
    And I should see the following lines of text:
      | A triple star system at a distance of 4.3 light years.         |
      | A low mass red dwarf at around 6 light years distance.         |
      | Wolf 359 is a red dwarf star located in the constellation Leo. |

    # Check that the subscriptions can be managed through the UI.
    Given I select the following subscription options for the "Alpha Centauri" collection:
      | Discussion | all |
      | Document   | all |
      | Event      | all |
      | News       | all |

    And I select the following subscription options for the "Barnard's Star" collection:
      | Discussion | none |
      | Document   | none |
      | Event      | none |
      | News       | none |

    And I select the following subscription options for the "Wolf 359" collection:
      | Discussion | all  |
      | Document   | none |
      | Event      | all  |
      | News       | none |

    And I press "Submit"
    Then I should see the success message "The subscriptions have been updated."
    And I should have the following collection content subscriptions:
      | Alpha Centauri | discussion, document, event, news |
      | Barnard's Star |                                   |
      | Wolf 359       | discussion, event                 |

  Scenario Outline: Change the notification frequency of my digests
    Given collection:
      | title | Malicious plans |
      | state | validated       |
    And discussion content:
      | title        | body                   | collection      | state     | author           |
      | Water supply | Contaminate it with GB | Malicious plans | validated | Auric Goldfinger |

    Given I am logged in as "Auric Goldfinger"
    And I am on the homepage
    When I click "My account"
    # Note that the link is located in the '3 dots menu' in the top right.
    And I click "Subscription settings" in the "Header" region
    Then I should see the heading "Subscription settings"
    When I select the radio button <radio button>
    And I press "Save"

    Given I am logged in as a moderator
    When I go to the discussion content "Water supply" edit screen
    And I fill in "Content" with "Contaminate it with Sarin"
    And I press "Update"
    Then the <frequency> digest for "Auric Goldfinger" should contain the following message for the "Water supply" node:
      | mail_subject | Joinup: The discussion "Water supply" was updated in the space of "Malicious plans" |
      | mail_body    | The discussion "Water supply" was updated in the "Malicious plans" collection.      |

    Examples:
      | radio button | frequency |
      | Daily        | daily     |
      | Weekly       | weekly    |
      | Monthly      | monthly   |

  @email
  Scenario: Choose to receive notifications immediately
    And collection:
      | title | Malicious plans |
      | state | validated       |
    And discussion content:
      | title        | body                   | collection      | state     | author           |
      | Water supply | Contaminate it with GB | Malicious plans | validated | Auric Goldfinger |

    Given I am logged in as "Auric Goldfinger"
    And I am on the homepage
    When I click "My account"
    # Note that the link is located in the '3 dots menu' in the top right.
    And I click "Subscription settings" in the "Header" region
    Then I should see the heading "Subscription settings"
    When I select the radio button "Immediately"
    And I press "Save"

    Given I am logged in as a moderator
    When I go to the discussion content "Water supply" edit screen
    And I fill in "Content" with "Contaminate it with Sarin"
    And I press "Update"
    Then the following email should have been sent:
      | recipient_mail | auric@example.com                                                                   |
      | subject        | Joinup: The discussion "Water supply" was updated in the space of "Malicious plans" |
      | body           | The discussion "Water supply" was updated in the "Malicious plans" collection.      |
