@api @terms @group-g
Feature: Notification test for the news transitions on a pre moderated parent.
  In order to manage my collections
  As an owner of the collection
  I want to receive a notification when an entity is proposed.

  Scenario: Notifications should be sent whenever a news is going through a relevant transition.
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
    And news content:
      | title                         | author         | body | headline                      | collection        | field_state      |
      # The next one belongs to a facilitator because there is no published version for that and thus,
      # the facilitator would not have access to the entity.
      | CCN pre publish               | CC facilitator | body | CCN pre publish               | CC pre collection | draft            |
      | CCN pre propose               | CC member      | body | CCN pre propose               | CC pre collection | draft            |
      | CCN pre request changes       | CC member      | body | CCN pre request changes       | CC pre collection | validated        |
      | CCN pre report                | CC member      | body | CCN pre report                | CC pre collection | validated        |
      | CCN pre request deletion      | CC member      | body | CCN pre request deletion      | CC pre collection | validated        |
      | CCN pre propose from reported | CC member      | body | CCN pre propose from reported | CC pre collection | needs_update     |
      | CCN pre approve proposed      | CC member      | body | CCN pre approve proposed      | CC pre collection | proposed         |
      | CCN pre reject deletion       | CC member      | body | CCN pre reject deletion       | CC pre collection | deletion_request |
      | CCN pre delete                | CC member      | body | CCN pre delete                | CC pre collection | deletion_request |
      | CCN validated to delete       | CC member      | body | CCN pre delete                | CC pre collection | validated        |
      | CCN validated to revise       | CC member      | body | CCN pre revise                | CC pre collection | validated        |

    # Test 'create' operation.
    When I am logged in as "CC member"
    And I go to the "CC pre collection" collection
    And I click "Add news" in the plus button menu
    And I fill in "Short title" with "CCN create propose"
    And I fill in "Headline" with "CCN create propose"
    And I fill in "Content" with "CCN create propose"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Propose"
    Then the email sent to "CC owner" with subject "Joinup: Content has been proposed" contains the following lines of text:
      | text                                                                                                              |
      | CC Member has submitted a new news - "CCN create propose" for publication in the collection: "CC pre collection". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                |

    # Regression test for proposing an item with a published version.
    When I am logged in as "CC facilitator"
    And I go to the "CCN create propose" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    And I am logged in as "CC member"
    And I go to the "CCN create propose" news
    And I click "Edit" in the "Entity actions" region
    And I press "Save new draft"
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | Notify moderator                                                                                                             |
      | subject   | Joinup: Content has been proposed                                                                                            |
      | body      | CC Member has submitted an update of the news - "CCN create propose" for publication in the collection: "CC pre collection". |
    And the email sent to "CC owner" with subject "Joinup: Content has been proposed" contains the following lines of text:
      | text                                                                                                                         |
      | CC Member has submitted an update of the news - "CCN create propose" for publication in the collection: "CC pre collection". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                           |

    When I am logged in as "CC facilitator"
    And I go to the "CC pre collection" collection
    And I click "Add news" in the plus button menu
    And I fill in "Short title" with "CCN create publish"
    And I fill in "Headline" with "CCN create publish"
    And I fill in "Content" with "CCN create publish"
    And I select "Statistics and Analysis" from "Topic"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                                                           |
      | subject   | Joinup: Content has been published                                                                                                                                 |
      | body      | CC Facilitator has published the new news - "CCN create publish" in the collection: "CC pre collection".You can access the new content at the following link: http |

    # Test 'update' operation.
    When I am logged in as "CC member"
    And I go to the "CCN pre propose" news
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the email sent to "CC owner" with subject "Joinup: Content has been proposed" contains the following lines of text:
      | text                                                                                                           |
      | CC Member has submitted a new news - "CCN pre propose" for publication in the collection: "CC pre collection". |
      | If you think this action is not clear or not due, please contact Joinup Support at                             |

    When I go to the "CCN pre propose from reported" news
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the email sent to "CC owner" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                  |
      | CC Member has updated the content of the news - "CCN pre propose from reported" as advised and requests again its publication in the collection: "CC pre collection". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                    |

    When I go to the "CCN pre request deletion" news
    And I click "Edit" in the "Entity actions" region
    And I press "Request deletion"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I just want to delete it."
    And I press "Request deletion"
    Then the email sent to "CC owner" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                        |
      | CC Member has requested to delete the news - "CCN pre request deletion" in the collection: "CC pre collection", with the following motivation: "I just want to delete it.". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                          |

    When I go to the "CCN validated to revise" news
    And I click "Edit" in the "Entity actions" region
    And I press "Propose changes"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                                                          |
      | subject   | Joinup: Content has been proposed                                                                                                 |
      | body      | CC Member has submitted an update of the news - "CCN validated to revise" for publication in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre publish" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC owner                                                                                              |
      | subject   | Joinup: Content has been published                                                                    |
      | body      | CC Facilitator has published the new news - "CCN pre publish" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre request changes" news
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you do some changes?"
    And I press "Request changes"
    Then the email sent to "CC member" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                                                |
      | the Facilitator, CC Facilitator has requested you to modify the news - "CCN pre request changes" in the collection: "CC pre collection", with the following motivation: "Can you do some changes?". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                                                  |
    But the following email should not have been sent:
      | recipient | CC owner                                                                                                                               |
      | subject   | Joinup: Content has been proposed                                                                                                      |
      | body      | CC Facilitator has submitted an update of the news - "CCN pre request changes" for publication in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre report" news
    And I click "Edit" in the "Entity actions" region
    And I press "Report"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Your content is reported"
    And I press "Request changes"
    Then the email sent to "CC member" with subject "Joinup: Content has been updated" contains the following lines of text:
      | text                                                                                                                                                                                       |
      | the Facilitator, CC Facilitator has requested you to modify the news - "CCN pre report" in the collection: "CC pre collection", with the following motivation: "Your content is reported". |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                                         |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre approve proposed" news
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                 |
      | subject   | Joinup: Content has been updated                                                                                                                          |
      | body      | the Facilitator, CC Facilitator has approved your request of publication of the news - "CCN pre approve proposed" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre reject deletion" news
    And I click "Edit" in the "Entity actions" region
    And I press "Reject deletion"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I still like it"
    And I press "Reject deletion"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                                                                              |
      | subject   | Joinup: Content has been updated                                                                                                                                                                       |
      | body      | the Facilitator, CC Facilitator has not approved your request to delete the news - "CCN pre reject deletion" in the collection: "CC pre collection", with the following motivation: "I still like it". |

    # Test 'delete' operation.
    When I am logged in as "CC facilitator"
    And I go to the "CCN pre delete" news
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                |
      | subject   | Joinup: Content has been deleted                                                                                                         |
      | body      | Facilitator CC Facilitator has approved your request of deletion for the news - "CCN pre delete" in the collection: "CC pre collection". |

    When I am logged in as "CC facilitator"
    And I go to the "CCN pre request deletion" news
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                                                          |
      | subject   | Joinup: Content has been deleted                                                                                                                   |
      | body      | Facilitator CC Facilitator has approved your request of deletion for the news - "CCN pre request deletion" in the collection: "CC pre collection". |

    # Test 'delete' operation for a validated entity.
    When I am logged in as "CC facilitator"
    And I go to the "CCN validated to delete" news
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | CC member                                                                                                           |
      | subject   | Joinup: Content has been deleted                                                                                    |
      | body      | Facilitator CC Facilitator has deleted the news - "CCN validated to delete" in the collection: "CC pre collection". |
