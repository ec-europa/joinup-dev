@api @terms @email
Feature: Notification test for the collection transitions.
  In order to manage my collections
  As an user that is related to the collection
  I want to receive a notification an event occurs.

  Scenario: Notifications should be sent whenever an event is occuring related to a collection.
    Given the following owner:
      | name       | type                    |
      | NC for all | Non-Profit Organisation |
    And users:
      | Username       | Roles     | E-mail                  | First name | Family name |
      | NC moderator   | moderator | nc_moderator@test.com   | NC         | Moderator   |
      | NC user        |           | nc_user@test.com        | NC         | User        |
      | NC owner       |           | nc_owner@test.com       | NC         | Owner       |
      | NC facilitator |           | nc_facilitator@test.com | NC         | Facilitator |
      | NC member1     |           | nc_member1@test.com     | NC         | Member1     |
      | NC member2     |           | nc_member2@test.com     | NC         | Member2     |
      | NCS owner      |           | ncs_owner@test.com      | NC         | Owner       |
    And the following solutions:
      | title        | logo     | banner     | state     |
      # Has only one affiliate.
      | NC Solution1 | logo.png | banner.jpg | validated |
      # Has more than one affiliate.
      | NC Solution2 | logo.png | banner.jpg | validated |
    And the following solution user memberships:
      | solution     | user      | roles |
      | NC Solution1 | NCS owner | owner |
      | NC Solution2 | NCS owner | owner |
    And collections:
      | title                  | state            | abstract     | description   | policy domain     | owner      | affiliates                 |
      | NC to propose          | draft            | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to validate         | proposed         | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      # The following will also cover the validate edited notification.
      | NC to propose edit     | validated        | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to validate edit    | validated        | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to request archival | validated        | No one cares | No one cares. | Supplier exchange | NC for all | NC Solution2               |
      | NC to request deletion | validated        | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to reject archival  | archival request | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to reject deletion  | deletion request | No one cares | No one cares. | Supplier exchange | NC for all |                            |
      | NC to archive          | archival request | No one cares | No one cares. | Supplier exchange | NC for all | NC Solution1, NC Solution2 |
      | NC to delete           | deletion request | No one cares | No one cares. | Supplier exchange | NC for all |                            |
    And the following collection user memberships:
      | collection             | user           | roles              |
      | NC to propose          | NC owner       | owner, facilitator |
      | NC to validate         | NC owner       | owner, facilitator |
      | NC to propose edit     | NC owner       | owner, facilitator |
      # A third person is needed to test notifications sent to both moderators and the owner.
      | NC to propose edit     | NC facilitator | facilitator        |
      | NC to validate edit    | NC owner       | owner, facilitator |
      | NC to request archival | NC owner       | owner, facilitator |
      | NC to request deletion | NC owner       | owner, facilitator |
      | NC to reject archival  | NC owner       | owner, facilitator |
      | NC to reject deletion  | NC owner       | owner, facilitator |
      | NC to archive          | NC owner       | owner, facilitator |
      | NC to archive          | NC member1     |                    |
      | NC to archive          | NC member2     |                    |
      | NC to delete           | NC owner       | owner, facilitator |
      | NC to delete           | NC member1     |                    |
      | NC to delete           | NC member2     |                    |

    # Test 'create' operation.
#    When all e-mails have been sent
#    And I am logged in as "NC user"
#    When I go to the propose collection form
#    When I fill in the following:
#      | Title            | NC proposed new |
#      | Description      | No one cares.   |
#      | Spatial coverage | Belgium         |
#    When I select "HR" from "Policy domain"
#    And I press "Add existing" at the "Owner" field
#    And I fill in "Owner" with "NC for all"
#    And I press "Propose"
#    Then the following email should have been sent:
#      | recipient | NC moderator                                                                                                                 |
#      | subject   | User NC proposed collection NC proposed new                                                                                        |
#      | body      | NC User has proposed collection "NC proposed new". To approve or reject this proposal, please go to |
#    Then I delete the "NC proposed new" collection

    # Test 'propose' operation (on an existing collection)
#    When all e-mails have been sent
#    And I am logged in as "NC owner"
#    And I go to the homepage of the "NC to propose" collection
#    And I click the contextual link "Edit" in the Header region
#    And I press "Propose"
#    Then the following email should have been sent:
#      | recipient | NC moderator                                                                                                                 |
#      | subject   | User NC proposed collection NC to propose                                                                                        |
#      | body      | NC Owner has proposed collection "NC to propose". To approve or reject this proposal, please go to |

    # Test 'request archival' operation.
#    When all e-mails have been sent
#    And I go to the homepage of the "NC to request archival" collection
#    And I click the contextual link "Edit" in the Header region
#    And I press "Request archival"
#    Then the following email should have been sent:
#      | recipient | NC moderator                                                                                                               |
#      | subject   | User NC requested to archive collection NC to request archival                                                             |
#      | body      | NC Owner has requested to archive the collection "NC to request archival". To approve or reject this request, please go to |

    # Test 'request deletion' operation.
#    When all e-mails have been sent
#    And I go to the homepage of the "NC to request deletion" collection
#    And I click the contextual link "Edit" in the Header region
#    And I press "Request deletion"
#    Then the following email should have been sent:
#      | recipient | NC moderator                                                                                                              |
#      | subject   | User NC requested to delete collection NC to request deletion                                                             |
#      | body      | NC Owner has requested to delete the collection "NC to request deletion". To approve or reject this request, please go to |

    # Test 'propose edit' operation.
    When all e-mails have been sent
    And I am logged in as "NC facilitator"
    And I go to the homepage of the "NC to propose edit" collection
    And I click the contextual link "Edit" in the Header region
    And I fill in "Title" with "NC to propose edit proposed"
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | NC moderator                                                                                                                   |
      | subject   | User NC proposed to edit collection NC to propose edit proposed                                                                |
      | body      | NC Facilitator has proposed to edit collection "NC to propose edit proposed". To approve or reject this proposal, please go to |
    And the following email should have been sent:
      | recipient | NC owner                                                                                                              |
      | subject   | User NC proposed to edit collection NC to propose edit proposed                                                       |
      | body      | NC Facilitator has proposed to edit collection "NC to propose edit proposed". To modify your collection, please go to |






#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC pre collection" collection
#    And I click "Add event" in the plus button menu
#    And I fill in "Title" with "CC notify create publish"
#    And I fill in "Description" with "CC notify create publish"
#    And I fill in "Location" with "CC notify create propose"
#    And I press "Publish"
#    Then the following email should have been sent:
#      | recipient | CC owner                                                                                                                                                                   |
#      | subject   | Joinup: Content has been published                                                                                                                                         |
#      | body      | CC Facilitator has published the new event - "CC notify create publish" in the collection: "CC pre collection". You can access the new content at the following link: http |
#
#    # Test 'update' operation.
#    When all e-mails have been sent
#    And I am logged in as "CC member"
#    And I go to the "CC notify pre propose" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Propose"
#    Then the following email should have been sent:
#      | recipient | CC owner                                                                                                              |
#      | subject   | Joinup: Content has been proposed                                                                                     |
#      | body      | CC Member has submitted a new event - "CC notify pre propose" for publication in the collection: "CC pre collection". |
#
#    When all e-mails have been sent
#    And I go to the "CC notify pre propose from reported" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Propose"
#    Then the following email should have been sent:
#      | recipient | CC owner                                                                                                                                                                     |
#      | subject   | Joinup: Content has been updated                                                                                                                                             |
#      | body      | CC Member has updated the content of the event - "CC notify pre propose from reported" as advised and requests again its publication in the collection: "CC pre collection". |
#
#    When all e-mails have been sent
#    And I go to the "CC notify pre request deletion" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Request deletion"
#    Then I should see the error message "This action requires you to fill in the motivation field"
#    When I fill in "Motivation" with "I just want to delete it."
#    And I press "Request deletion"
#    Then the following email should have been sent:
#      | recipient | CC owner                                                                                                                                                                           |
#      | subject   | Joinup: Content has been updated                                                                                                                                                   |
#      | body      | CC Member has requested to delete the event - "CC notify pre request deletion" in the collection: "CC pre collection", with the following motivation: "I just want to delete it.". |
#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre publish" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Publish"
#    Then the following email should have been sent:
#      | recipient | CC owner                                                                                                     |
#      | subject   | Joinup: Content has been published                                                                           |
#      | body      | CC Facilitator has published the new event - "CC notify pre publish" in the collection: "CC pre collection". |
#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre request changes" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Request changes"
#    Then I should see the error message "This action requires you to fill in the motivation field"
#    When I fill in "Motivation" with "Can you do some changes?"
#    And I press "Request changes"
#    Then the following email should have been sent:
#      | recipient | CC member                                                                                                                                                                                                  |
#      | subject   | Joinup: Content has been updated                                                                                                                                                                           |
#      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify pre request changes" in the collection: "CC pre collection", with the following motivation: "Can you do some changes?". |
#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre report" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Report"
#    Then I should see the error message "This action requires you to fill in the motivation field"
#    When I fill in "Motivation" with "Your content is reported"
#    And I press "Request changes"
#    Then the following email should have been sent:
#      | recipient | CC member                                                                                                                                                                                         |
#      | subject   | Joinup: Content has been updated                                                                                                                                                                  |
#      | body      | the Facilitator, CC Facilitator has requested you to modify the event - "CC notify pre report" in the collection: "CC pre collection", with the following motivation: "Your content is reported". |
#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre approve proposed" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Publish"
#    Then the following email should have been sent:
#      | recipient | CC member                                                                                                                                                 |
#      | subject   | Joinup: Content has been updated                                                                                                                          |
#      | body      | the Facilitator, CC Facilitator has approved your request to publish the event - "CC notify pre approve proposed" in the collection: "CC pre collection". |
#
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre reject deletion" event
#    And I click "Edit" in the "Entity actions" region
#    And I press "Reject deletion"
#    Then I should see the error message "This action requires you to fill in the motivation field"
#    When I fill in "Motivation" with "I still like it"
#    And I press "Reject deletion"
#    Then the following email should have been sent:
#      | recipient | CC member                                                                                                                                                                                                     |
#      | subject   | Joinup: Content has been updated                                                                                                                                                                              |
#      | body      | the Facilitator, CC Facilitator has not approved your request to delete the event - "CC notify pre reject deletion" in the collection: "CC pre collection", with the following motivation: "I still like it". |
#
#    # Test 'delete' operation.
#    When all e-mails have been sent
#    And I am logged in as "CC facilitator"
#    And I go to the "CC notify pre delete" event
#    And I click "Edit" in the "Entity actions" region
#    And I click "Delete"
#    And I press "Delete"
#    Then the following email should have been sent:
#      | recipient | CC member                                                                                                         |
#      | subject   | Joinup: Content has been deleted                                                                                  |
#      | body      | Facilitator CC Facilitator has deleted the event - "CC notify pre delete" in the collection: "CC pre collection". |
