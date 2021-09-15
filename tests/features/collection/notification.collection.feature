@api @terms @group-a
Feature: Notification test for the collection transitions.
  In order to manage my collections
  As an user that is related to the collection
  I want to receive a notification when an event occurs

  Scenario: Notifications should be sent whenever an event is occurring related to a collection.
    Given the following owner:
      | name       | type                    |
      | NC for all | Non-Profit Organisation |
    And the following contact:
      | name  | Notificationous absolutous        |
      | email | absolute.notification@example.com |
    And users:
      | Username       | Roles     | E-mail                  | First name | Family name |
      | NC moderator   | moderator | nc_moderator@test.com   | NC         | Moderator   |
      | NC user        |           | nc_user@test.com        | NC         | User        |
      | NC owner       |           | nc_owner@test.com       | NC         | Owner       |
      | NC facilitator |           | nc_facilitator@test.com | NC         | Facilitator |
      | NC member1     |           | nc_member1@test.com     | NC         | Member1     |
      | NC member2     |           | nc_member2@test.com     | NC         | Member2     |
      | NCS owner      |           | ncs_owner@test.com      | NC         | Owner       |
    And collections:
      | title                  | state            | abstract     | description   | topic             | owner      | contact information        |
      | NC to propose          | draft            | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to validate         | proposed         | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      # The following will also cover the validate edited notification.
      | NC to propose edit     | validated        | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to validate edit    | validated        | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to request archival | validated        | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to reject archival  | archival request | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to archive          | archival request | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to delete           | validated        | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
      | NC to delete by mod    | validated        | No one cares | No one cares. | Supplier exchange | NC for all | Notificationous absolutous |
    And the following solutions:
      | title        | collections                          | logo     | banner     | state     |
      # Has only one affiliate.
      | NC Solution1 | NC to archive                        | logo.png | banner.jpg | validated |
      # Has more than one affiliate.
      | NC Solution2 | NC to request archival,NC to archive | logo.png | banner.jpg | validated |
    And the following solution user memberships:
      | solution     | user      | roles |
      | NC Solution1 | NCS owner | owner |
      | NC Solution2 | NCS owner | owner |
    And the following collection user memberships:
      | collection             | user           | roles              |
      | NC to propose          | NC owner       | owner, facilitator |
      | NC to validate         | NC owner       | owner, facilitator |
      | NC to propose edit     | NC owner       | owner, facilitator |
      # A third person is needed to test notifications sent to both moderators and the owner.
      | NC to propose edit     | NC facilitator | facilitator        |
      | NC to validate edit    | NC owner       | owner, facilitator |
      | NC to request archival | NC owner       | owner, facilitator |
      | NC to reject archival  | NC owner       | owner, facilitator |
      | NC to archive          | NC owner       | owner, facilitator |
      | NC to archive          | NC member1     |                    |
      | NC to archive          | NC member2     |                    |
      | NC to delete           | NC owner       | owner, facilitator |
      | NC to delete           | NC member1     |                    |
      | NC to delete           | NC member2     |                    |
      | NC to delete by mod    | NC owner       | owner, facilitator |
      | NC to delete by mod    | NC member1     |                    |
      | NC to delete by mod    | NC member2     |                    |

    # Test 'create' operation.
    And I am logged in as "NC user"
    When I go to the propose collection form
    When I fill in the following:
      | Title                 | NC proposed new     |
      | Description           | No one cares.       |
      | Geographical coverage | Belgium             |
      # Contact information data.
      | Name                  | Super Sayan Academy |
      | E-mail                | ssa@example.com     |
    When I select "HR" from "Topic"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "NC for all"
    And I press "Propose"
    Then the email sent to "NC moderator" with subject "User NC proposed collection NC proposed new" contains the following lines of text:
      | text                                                                               |
      | NC User has proposed collection "NC proposed new".                                 |
      | To approve or reject this proposal, please go to                                   |
      | If you think this action is not clear or not due, please contact Joinup Support at |

    # Clean up the manually created entities.
    Then I delete the "NC proposed new" collection
    And I delete the "Super Sayan Academy" contact information

    # Test 'propose' operation (on an existing collection)
    And I am logged in as "NC owner"
    And I go to the homepage of the "NC to propose" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the email sent to "NC moderator" with subject "User NC proposed collection NC to propose" contains the following lines of text:
      | text                                                                               |
      | NC Owner has proposed collection "NC to propose".                                  |
      | To approve or reject this proposal, please go to                                   |
      | If you think this action is not clear or not due, please contact Joinup Support at |

    # Test 'request archival' operation.
    And I go to the homepage of the "NC to request archival" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Request archival"
    Then the email sent to "NC moderator" with subject "User NC requested to archive collection NC to request archival" contains the following lines of text:
      | text                                                                               |
      | NC Owner has requested to archive the collection "NC to request archival".         |
      | To approve or reject this request, please go to                                    |
      | If you think this action is not clear or not due, please contact Joinup Support at |

    # Test deletion of a collection by the owner.
    When I mark all emails as read
    And I go to the homepage of the "NC to delete" collection
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then 2 e-mails should have been sent
    Then the following email should have been sent:
      | recipient | NC member1                                                                  |
      | subject   | The collection NC to delete was deleted.                                    |
      | body      | The collection "NC to delete", of which you are a member, has been deleted. |
    And the following email should have been sent:
      | recipient | NC member2                                                                  |
      | subject   | The collection NC to delete was deleted.                                    |
      | body      | The collection "NC to delete", of which you are a member, has been deleted. |

    # Test 'propose edit' operation.
    And I am logged in as "NC facilitator"
    And I go to the homepage of the "NC to propose edit" collection
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "NC to propose edit proposed"
    And I press "Propose"
    Then the email sent to "NC moderator" with subject "User NC proposed to edit collection NC to propose edit proposed" contains the following lines of text:
      | text                                                                          |
      | NC Facilitator has proposed to edit collection "NC to propose edit proposed". |
      | To approve or reject this proposal, please go to                              |
    And the email sent to "NC owner" with subject "User NC proposed to edit collection NC to propose edit proposed" contains the following lines of text:
      | text                                                                          |
      | NC Facilitator has proposed to edit collection "NC to propose edit proposed". |
      | To modify your collection, please go to                                       |

    # Test the 'approve new' operation.
    And I am logged in as "NC moderator"
    And I go to the homepage of the "NC to validate" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | NC owner                                                                          |
      | subject   | Your proposal of collection NC to validate has been approved                      |
      | body      | Your proposed collection "NC to validate" has been validated as per your request. |

    # Test the 'approve proposed' that was proposed through the 'propose edit' operation.
    And I go to the homepage of the "NC to propose edit proposed" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the email sent to "NC owner" with subject "Your request to edit collection NC to propose edit proposed has been approved." contains the following lines of text:
      | text                                                                                  |
      | Your proposal to edit the collection "NC to propose edit proposed" has been accepted. |
      | You can verify the edited version of the collection at                                |

    # Test the 'reject archival' operation.
    And I go to the homepage of the "NC to reject archival" collection
    And I click "Edit" in the "Entity actions" region
    # @todo: This should change into a separate transition.
    And I press "Publish"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "It will not be archived."
    And I press "Publish"
    Then the email sent to "NC owner" with subject "Your request to archive collection NC to reject archival has been rejected" contains the following lines of text:
      | text                                                                                      |
      | NC Moderator has rejected your request to archive the collection "NC to reject archival". |
      | The reason for rejection is: It will not be archived.                                     |

    # Test the 'archive' operation.
    And I go to the homepage of the "NC to archive" collection
    And I click "Edit" in the "Entity actions" region
    And I press "Archive"
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "As you wish."
    And I press "Archive"
    Then the following email should have been sent:
      | recipient | NC owner                                                              |
      | subject   | Your request to archive collection NC to archive has been approved.   |
      | body      | The collection "NC to archive" has been archived as per your request. |
    And the following email should have been sent:
      | recipient | NCS owner                                                                                                                                                                                                                                         |
      | subject   | The collection NC to archive was archived. Your solution was affiliated only to the collection NC to archive, and as a consequence, your solution is not currently affiliated to any other collection. Please verify and take appropriate action. |
      | body      | Since your solution "NC Solution1" was affiliated only with this archived collection, your solution is currently no longer affiliated to any other collection.                                                                                    |
    And the following email should have been sent:
      | recipient | NCS owner                                                                                                                                                       |
      | subject   | The collection NC to archive was archived                                                                                                                       |
      | body      | The "NC to archive" collection, to which your "NC Solution2" solution was affiliated, was recently archived. Please verify the updated details of your solution |
    And the following email should have been sent:
      | recipient | NC member1                                                                                                                   |
      | subject   | The collection NC to archive was archived.                                                                                   |
      | body      | The collection "NC to archive", of which you are a member, has been archived. The reason for being archived is: As you wish. |
    And the following email should have been sent:
      | recipient | NC member2                                                                                                                   |
      | subject   | The collection NC to archive was archived.                                                                                   |
      | body      | The collection "NC to archive", of which you are a member, has been archived. The reason for being archived is: As you wish. |

    # Test the deletion of a collection by a moderator. This should also inform
    # the collection owner.
    When I mark all emails as read
    And I go to the homepage of the "NC to delete by mod" collection
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then 3 e-mails should have been sent
    Then the following email should have been sent:
      | recipient | NC member1                                                                         |
      | subject   | The collection NC to delete by mod was deleted.                                    |
      | body      | The collection "NC to delete by mod", of which you are a member, has been deleted. |
    And the following email should have been sent:
      | recipient | NC member2                                                                         |
      | subject   | The collection NC to delete by mod was deleted.                                    |
      | body      | The collection "NC to delete by mod", of which you are a member, has been deleted. |
    And the following email should have been sent:
      | recipient | NC owner                                                              |
      | subject   | Joinup: Your collection has been deleted by the moderation team       |
      | body      | The Joinup moderation team deleted the collection NC to delete by mod |
