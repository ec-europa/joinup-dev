@api @terms @group-b
Feature: Notification test for the discussion transitions on a post moderated parent.
  In order to manage my collections
  As an owner of the collection
  I want to receive a notification when an entity is proposed.

  Scenario Outline: Notifications should be sent whenever a discussion is going through a relevant transition.
    Given users:
      | Username         | Roles     | E-mail                      | First name | Family name | Notification frequency |
      | Notify moderator | moderator | notify_moderator@test.com   | Notify     | Moderator   | immediate              |
      | CC owner         |           | notify_owner@test.com       | CC         | Owner       | immediate              |
      | CC facilitator   |           | notify_facilitator@test.com | CC         | Facilitator | immediate              |
      | CC member        |           | notify_member@test.com      | CC         | Member      | immediate              |
    And collections:
      | title              | state     | content creation | moderation   |
      | CC post collection | validated | members          | <moderation> |
    And the following collection user memberships:
      | collection         | user           | roles       |
      | CC post collection | CC owner       | owner       |
      | CC post collection | CC facilitator | facilitator |
      | CC post collection | CC member      | <roles>     |
    And discussion content:
      | title                                | author    | body | collection         | field_state  |
      | CC notify post publish               | CC member | body | CC post collection | draft        |
      | CC notify post request changes       | CC member | body | CC post collection | validated    |
      | CC notify post report                | CC member | body | CC post collection | validated    |
      | CC notify post propose from reported | CC member | body | CC post collection | needs_update |
      | CC notify post approve proposed      | CC member | body | CC post collection | proposed     |
      | CC notify post delete                | CC member | body | CC post collection | validated    |

    # Test 'create' operation.
    And I am logged in as "CC member"
    And I go to the "CC post collection" collection
    And I click "Add discussion" in the plus button menu
    And I fill in "Title" with "CC notify create publish"
    And I fill in "Content" with "CC notify create publish"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                                   |
      | subject   | Joinup: Content has been published                                                                                                                                         |
      | body      | CC Member has published the new discussion - "CC notify create publish" in the collection: "CC post collection".You can access the new content at the following link: http |

    # Test 'update' operation.
    When I am logged in as "CC member"
    And I go to the "CC notify post publish" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                       |
      | subject   | Joinup: Content has been published                                                                             |
      | body      | CC Member has published the new discussion - "CC notify post publish" in the collection: "CC post collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify post request changes" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you do some changes?"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                                         |
      | subject   | Joinup: Content has been updated                                                                                                                                                                                  |
      | body      | the Facilitator, CC Facilitator has requested you to modify the discussion - "CC notify post request changes" in the collection: "CC post collection", with the following motivation: "Can you do some changes?". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify post report" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Report"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Your content is reported"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                                |
      | subject   | Joinup: Content has been updated                                                                                                                                                                         |
      | body      | the Facilitator, CC Facilitator has requested you to modify the discussion - "CC notify post report" in the collection: "CC post collection", with the following motivation: "Your content is reported". |

    When I mark all emails as read
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post approve proposed" discussion
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then 1 e-mail should have been sent
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                               |
      | subject   | Joinup: Content has been updated                                                                                                                                        |
      | body      | the Facilitator, CC Facilitator has approved your request of publication of the discussion - "CC notify post approve proposed" in the collection: "CC post collection". |

    # Test 'delete' operation.
    When I mark all emails as read
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post delete" discussion
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then 1 e-mail should have been sent
    Then the following email should not have been sent:
      | recipient | CC member                                                                                                                |
      | subject   | Joinup: Content has been deleted                                                                                         |
      | body      | Facilitator CC Facilitator has deleted the discussion - "CC notify post delete" in the collection: "CC post collection". |
    But the following email should have been sent:
      | recipient | CC member                                                                                                          |
      | subject   | Joinup: The discussion "CC notify post delete" was deleted in the space of "CC post collection"                    |
      | body      | for your information, the discussion "CC notify post delete" was deleted from the "CC post collection" collection. |

    Examples:
      | moderation | roles  |
      | no         |        |
      | yes        | author |

  Scenario: No notifications should be sent when a discussion is orphaned
    Given collections:
      | title                   | state     |
      | Event Horizon Telescope | validated |
    And discussion content:
      | title                            | collection              | state     |
      | How do the jets fire into space? | Event Horizon Telescope | validated |
    And users:
      | Username         |
      | Lindsey McCray   |
      | Cambria Falconer |
      | Monroe Fearchar  |
    And collection user memberships:
      | collection              | user           | roles              |
      | Event Horizon Telescope | Lindsey McCray | owner, facilitator |
    And discussion subscriptions:
      | username         | title                            |
      | Cambria Falconer | How do the jets fire into space? |
    And comments:
      | message              | author          | parent                           |
      | Huge magnetic fields | Monroe Fearchar | How do the jets fire into space? |

    When I mark all emails as read
    And I am logged in as a moderator
    And I go to the homepage of the "Event Horizon Telescope" collection
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | Lindsey McCray                                                            |
      | subject   | Joinup: Your collection has been deleted by the moderation team           |
      | body      | The Joinup moderation team deleted the collection Event Horizon Telescope |
    And 1 e-mail should have been sent
