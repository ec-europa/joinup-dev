@api @terms @group-b
Feature: Notification test for the event transitions on a pre moderated parent.
  In order to manage my collections
  As an owner of the collection
  I want to receive a notification when an entity is proposed.

  Scenario: Notifications should be sent whenever a event is going through a relevant transition.
    Given users:
      | Username         | Roles     | E-mail                      | First name | Family name |
      | Notify moderator | moderator | notify_moderator@test.com   | Notify     | Moderator   |
      | CC owner         |           | notify_owner@test.com       | CC         | Owner       |
      | CC facilitator   |           | notify_facilitator@test.com | CC         | Facilitator |
      | CC member        |           | notify_member@test.com      | CC         | Member      |
    And collections:
      | title             | state     | content creation | moderation |
      | CC pre collection | validated | members          | yes        |
    And the following collection user memberships:
      | collection        | user           | roles       |
      | CC pre collection | CC owner       | owner       |
      | CC pre collection | CC facilitator | facilitator |
      | CC pre collection | CC member      |             |
    And event content:
      | title                               | author         | body | location                             | collection        | field_state      |
      # The next one belongs to a facilitator because there is no published version for that and thus,
      # the facilitator would not have access to the entity.
      | CC notify pre publish               | CC facilitator | body | Half Moon Street, London             | CC pre collection | draft            |
      | CC notify pre propose               | CC member      | body | Quai d'Orleans, Paris                | CC pre collection | draft            |
      | CC notify pre request changes       | CC member      | body | Rue du Mont Thabor, Paris            | CC pre collection | validated        |
      | CC notify pre report                | CC member      | body | Cort Adelers Gate, Oslo              | CC pre collection | validated        |
      | CC notify pre request deletion      | CC member      | body | Ferdinand Bolstraat, Amsterdam       | CC pre collection | validated        |
      | CC notify pre propose from reported | CC member      | body | Lychener Strasse, Berlin             | CC pre collection | needs_update     |
      | CC notify pre approve proposed      | CC member      | body | Capel Street, Dublin                 | CC pre collection | proposed         |
      | CC notify pre reject deletion       | CC member      | body | Carrer dels Sagristans, Barcelona    | CC pre collection | deletion_request |
      | CC notify pre delete                | CC member      | body | 4 Chome-2-15 Ginza, Chuo City, Tokyo | CC pre collection | deletion_request |
      | CC notify validated to delete       | CC member      | body | Malcolm X Boulevard, New York        | CC pre collection | validated        |
      | CC notify validated to revise       | CC member      | body | Malcolm X Boulevard, New York        | CC pre collection | validated        |

    # Test 'create' operation.
    When I am logged in as "CC member"
    And I go to the "CC pre collection" collection
    And I click "Add event" in the plus button menu
    And I fill in "Title" with "CC notify create propose"
    And I fill in "Description" with "CC notify create propose"
    And I fill in "Physical location" with "South King Street, Honolulu"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                 |
      | subject   | Joinup: Content has been proposed                                                                                        |
      | body      | CC Member has submitted a new event - "CC notify create propose" for publication in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC pre collection" collection
    And I click "Add event" in the plus button menu
    And I fill in "Title" with "CC notify create publish"
    And I fill in "Description" with "CC notify create publish"
    And I fill in "Physical location" with "West Broadway, Vancouver"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                                  |
      | subject   | Joinup: Content has been published                                                                                                                                        |
      | body      | CC Facilitator has published the new event - "CC notify create publish" in the collection: "CC pre collection".You can access the new content at the following link: http |

    # Test 'update' operation.
    When I am logged in as "CC member"
    And I go to the "CC notify pre propose" event
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                              |
      | subject   | Joinup: Content has been proposed                                                                                     |
      | body      | CC Member has submitted a new event - "CC notify pre propose" for publication in the collection: "CC pre collection". |

    When I go to the "CC notify pre propose from reported" event
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                                     |
      | subject   | Joinup: Content has been updated                                                                                                                                             |
      | body      | CC Member has updated the content of the event - "CC notify pre propose from reported" as advised and requests again its publication in the collection: "CC pre collection". |

    When I go to the "CC notify pre request deletion" event
    And I click "Edit" in the "Entity actions" region
    And I press "Request deletion"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I just want to delete it."
    And I press "Request deletion"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                                           |
      | subject   | Joinup: Content has been updated                                                                                                                                                   |
      | body      | CC Member has requested to delete the event - "CC notify pre request deletion" in the collection: "CC pre collection", with the following motivation: "I just want to delete it.". |

    When I go to the "CC notify validated to revise" event
    And I click "Edit" in the "Entity actions" region
    And I press "Propose changes"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                 |
      | subject   | Joinup: Content has been proposed                                                                                                        |
      | body      | CC Member has submitted an update of the event - "CC notify validated to revise" for publication in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre publish" event
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                     |
      | subject   | Joinup: Content has been published                                                                           |
      | body      | CC Facilitator has published the new event - "CC notify pre publish" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre request changes" event
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you do some changes?"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                                  |
      | subject   | Joinup: Content has been updated                                                                                                                                                                           |
      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify pre request changes" in the collection: "CC pre collection", with the following motivation: "Can you do some changes?". |
    But the following email should not have been sent:
      | recipient | CC owner                                                                                                                                      |
      | subject   | Joinup: Content has been proposed                                                                                                             |
      | body      | CC Facilitator has submitted an update of the event - "CC notify pre request changes" for publication in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre report" event
    And I click "Edit" in the "Entity actions" region
    And I press "Report"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Your content is reported"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                         |
      | subject   | Joinup: Content has been updated                                                                                                                                                                  |
      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify pre report" in the collection: "CC pre collection", with the following motivation: "Your content is reported". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre approve proposed" event
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                        |
      | subject   | Joinup: Content has been updated                                                                                                                                 |
      | body      | the Facilitator, CC Facilitator has approved your request of publication of the event - "CC notify pre approve proposed" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre reject deletion" event
    And I click "Edit" in the "Entity actions" region
    And I press "Reject deletion"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I still like it"
    And I press "Reject deletion"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                                     |
      | subject   | Joinup: Content has been updated                                                                                                                                                                              |
      | body      | the Facilitator, CC Facilitator has not approved your request to delete the event - "CC notify pre reject deletion" in the collection: "CC pre collection", with the following motivation: "I still like it". |

    # Test 'delete' operation on an entity in 'deletion_request' state.
    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre delete" event
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                       |
      | subject   | Joinup: Content has been deleted                                                                                                                |
      | body      | Facilitator CC Facilitator has approved your request of deletion for the event - "CC notify pre delete" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CC notify pre request deletion" event
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                 |
      | subject   | Joinup: Content has been deleted                                                                                                                          |
      | body      | Facilitator CC Facilitator has approved your request of deletion for the event - "CC notify pre request deletion" in the collection: "CC pre collection". |

    # Test 'delete' operation on an entity in 'validated' state.
    When I am logged in as "CC facilitator"
    And I go to the "CC notify validated to delete" event
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                  |
      | subject   | Joinup: Content has been deleted                                                                                           |
      | body      | Facilitator CC Facilitator has deleted the event - "CC notify validated to delete" in the collection: "CC pre collection". |
