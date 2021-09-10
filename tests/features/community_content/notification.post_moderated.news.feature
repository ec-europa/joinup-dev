@api @terms @group-b
Feature: Notification test for the news transitions on a post moderated parent.
  In order to manage my collections
  As an owner of the collection
  I want to receive a notification when an entity is proposed.

  Scenario Outline: Notifications should be sent whenever a news is going through a relevant transition.
    Given users:
      | Username         | Roles     | E-mail                      | First name | Family name |
      | Notify moderator | moderator | notify_moderator@test.com   | Notify     | Moderator   |
      | CC owner         |           | notify_owner@test.com       | CC         | Owner       |
      | CC facilitator   |           | notify_facilitator@test.com | CC         | Facilitator |
      | CC member        |           | notify_member@test.com      | CC         | Member      |
    And collections:
      | title              | state     | content creation | moderation   |
      | CC post collection | validated | members          | <moderation> |
    And the following collection user memberships:
      | collection         | user           | roles       |
      | CC post collection | CC owner       | owner       |
      | CC post collection | CC facilitator | facilitator |
      | CC post collection | CC member      | <roles>     |
    And news content:
      | title                          | author    | body | headline                       | collection         | field_state  |
      | CCN post publish               | CC member | body | CCN post publish               | CC post collection | draft        |
      | CCN post request changes       | CC member | body | CCN post request changes       | CC post collection | validated    |
      | CCN post report                | CC member | body | CCN post report                | CC post collection | validated    |
      | CCN post propose from reported | CC member | body | CCN post propose from reported | CC post collection | needs_update |
      | CCN post approve proposed      | CC member | body | CCN post approve proposed      | CC post collection | proposed     |
      | CCN post delete                | CC member | body | CCN post delete                | CC post collection | validated    |

    # Test 'create' operation.
    When I am logged in as "CC member"
    And I go to the "CC post collection" collection
    And I click "Add news" in the plus button menu
    And I fill in "Short title" with "CCN create publish"
    And I fill in "Headline" with "CCN create publish"
    And I fill in "Content" with "CCN create publish"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                       |
      | subject   | Joinup: Content has been published                                                                                                                             |
      | body      | CC Member has published the new news - "CCN create publish" in the collection: "CC post collection".You can access the new content at the following link: http |

    # Test 'update' operation.
    When I am logged in as "CC member"
    And I go to the "CCN post publish" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                           |
      | subject   | Joinup: Content has been published                                                                 |
      | body      | CC Member has published the new news - "CCN post publish" in the collection: "CC post collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN post request changes" news
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you do some changes?"
    And I press "Request changes"
    Then the email sent to "CC member" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                                                  |
      | the Facilitator, CC Facilitator has requested you to modify the news - "CCN post request changes" in the collection: "CC post collection", with the following motivation: "Can you do some changes?". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                                                    |

    When I am logged in as "CC facilitator"
    And I go to the "CCN post report" news
    And I click "Edit" in the "Entity actions" region
    And I press "Report"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Your content is reported"
    And I press "Request changes"
    Then the email sent to "CC member" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                                         |
      | the Facilitator, CC Facilitator has requested you to modify the news - "CCN post report" in the collection: "CC post collection", with the following motivation: "Your content is reported". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                                           |

    When I am logged in as "CC facilitator"
    And I go to the "CCN post approve proposed" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                   |
      | subject   | Joinup: Content has been updated                                                                                                                            |
      | body      | the Facilitator, CC Facilitator has approved your request of publication of the news - "CCN post approve proposed" in the collection: "CC post collection". |

    # Test 'delete' operation.
    When I am logged in as "CC facilitator"
    And I go to the "CCN post delete" news
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                    |
      | subject   | Joinup: Content has been deleted                                                                             |
      | body      | Facilitator CC Facilitator has deleted the news - "CCN post delete" in the collection: "CC post collection". |

    Examples:
      | moderation | roles  |
      | no         |        |
      | yes        | author |
