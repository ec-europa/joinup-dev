@api
Feature: User subscription settings
  As a user I must be able to set and view my subscription settings.

  Scenario Outline: Change the notification frequency of my digests
    Given user:
      | Username   | Auric Goldfinger  |
      | Password   | oddjob            |
      | E-mail     | auric@example.com |
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
    Given user:
      | Username   | Auric Goldfinger  |
      | Password   | oddjob            |
      | E-mail     | auric@example.com |
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
      | recipient | auric@example.com                                                                   |
      | subject   | Joinup: The discussion "Water supply" was updated in the space of "Malicious plans" |
      | body      | The discussion "Water supply" was updated in the "Malicious plans" collection.      |

  @wip
  Scenario: Temporarily disabled scenario, will be restored in ISAICP-4218.
    # Todo: rewrite these steps in proper domain specific language.
    # Check "Receive notifications for": Solution.
    Then I check "field_user_group_types[rdf_entity:solution]"
    # Check "Receive notifications for": News.
    Then I check "field_user_group_types[node:news]"
    # Check "Notify me on updates".
    And I check "field_user_subscription_events[value]"
    And I press "Save"
    Then I should see the success message "The changes have been saved"

    # Check that the settings have been saved.
    When I am on the homepage
    And I click "My account"
    And I click "Subscription Settings"
    Then the radio button "Monthly" from field "Notification frequency" should be selected
    And the "Solution" checkbox should be checked
    And the "News" checkbox should be checked
    And the "Notify me on updates" checkbox should be checked
    But the "Release" checkbox should not be checked
    And the "Distribution" checkbox should not be checked
    And the "Document" checkbox should not be checked
    And the "Event" checkbox should not be checked
    And the "Custom page" checkbox should not be checked
