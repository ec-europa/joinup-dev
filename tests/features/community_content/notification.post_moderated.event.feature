@api @terms @group-b
Feature: Notification test for the event transitions on a post moderated parent.
  In order to manage my collections
  As an owner of the collection
  I want to receive a notification when an entity is proposed.

  Scenario Outline: Notifications should be sent whenever a event is going through a relevant transition.
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
    And event content:
      | title                                | author    | body | location                       | collection         | field_state  |
      | CC notify post publish               | CC member | body | Half Moon Street, London       | CC post collection | draft        |
      | CC notify post request changes       | CC member | body | Quai d'Orleans, Paris          | CC post collection | validated    |
      | CC notify post report                | CC member | body | Rue du Mont Thabor, Paris      | CC post collection | validated    |
      | CC notify post propose from reported | CC member | body | Cort Adelers Gate, Oslo        | CC post collection | needs_update |
      | CC notify post approve proposed      | CC member | body | Ferdinand Bolstraat, Amsterdam | CC post collection | proposed     |
      | CC notify post delete                | CC member | body | Lychener Strasse, Berlin       | CC post collection | validated    |

    # Test 'create' operation.
    When all e-mails have been sent
    And I am logged in as "CC member"
    And I go to the "CC post collection" collection
    And I click "Add event" in the plus button menu
    And I fill in "Title" with "CC notify create publish"
    And I fill in "Description" with "CC notify create publish"
    And I fill in "Physical location" with "Capel Street, Dublin"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                              |
      | subject   | Joinup: Content has been published                                                                                                                                    |
      | body      | CC Member has published the new event - "CC notify create publish" in the collection: "CC post collection".You can access the new content at the following link: http |

    # Test 'update' operation.
    When all e-mails have been sent
    And I am logged in as "CC member"
    And I go to the "CC notify post publish" event
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                  |
      | subject   | Joinup: Content has been published                                                                        |
      | body      | CC Member has published the new event - "CC notify post publish" in the collection: "CC post collection". |

    When all e-mails have been sent
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post request changes" event
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you do some changes?"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                                    |
      | subject   | Joinup: Content has been updated                                                                                                                                                                             |
      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify post request changes" in the collection: "CC post collection", with the following motivation: "Can you do some changes?". |

    When all e-mails have been sent
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post report" event
    And I click "Edit" in the "Entity actions" region
    And I press "Report"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Your content is reported"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                           |
      | subject   | Joinup: Content has been updated                                                                                                                                                                    |
      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify post report" in the collection: "CC post collection", with the following motivation: "Your content is reported". |

    When all e-mails have been sent
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post approve proposed" event
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                          |
      | subject   | Joinup: Content has been updated                                                                                                                                   |
      | body      | the Facilitator, CC Facilitator has approved your request of publication of the event - "CC notify post approve proposed" in the collection: "CC post collection". |

    # Test 'delete' operation.
    When all e-mails have been sent
    And I am logged in as "CC facilitator"
    And I go to the "CC notify post delete" event
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                           |
      | subject   | Joinup: Content has been deleted                                                                                    |
      | body      | Facilitator CC Facilitator has deleted the event - "CC notify post delete" in the collection: "CC post collection". |

    Examples:
      | moderation | roles  |
      | no         |        |
      | yes        | author |
