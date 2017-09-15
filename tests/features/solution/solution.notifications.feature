# Tests notifications for solutions. This file does not include template 1.
# Template 1 is tested in the add_solution.feature already.
@api @terms @email
Feature: Solution notifications
  In order to manage solutions
  As a user of the website
  I need to receive email that inform me regarding solution transitions.

  Scenario: Notifications are sent every time a related transition is applied to a solution.
    Given the following collection:
      | title | Collection of random solutions |
      | logo  | logo.png                       |
      | state | validated                      |
    And the following owner:
      | name               | type                  |
      | Karanikolas Kitsos | Private Individual(s) |
    And the following contact:
      | name  | Information Desk             |
      | email | information.desk@example.com |
    And users:
      | Username     | Roles     | First name | Family name |
      | Pat Harper   | moderator | Pat        | Harper      |
      | Ramiro Myers |           | Ramiro     | Myers       |
      | Edith Poole  |           | Edith      | Poole       |
    And the following solutions:
      | title                                                 | author       | description | logo     | banner     | owner              | contact information | state            | policy domain | solution type     | collection                     |
      | Solution notification to propose changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to request deletion             | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to approve deletion             | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | deletion request | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to reject deletion              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | deletion request | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to blacklist                    | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to publish from blacklisted     | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | blacklisted      | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to request changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to propose from request changes | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | needs update     | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to delete                       | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |

    When I am logged in as "Pat Harper"

    # Template 7. The moderation team proposes changes.
    And all e-mails have been sent
    And I go to the homepage of the "Solution notification to propose changes" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    # Motivation required.
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Please, check my updates"
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                                           |
      | subject   | Joinup: Changes have been proposed for your solution                                                                                                                                   |
      | body      | The Joinup Support Team has requested you to modify the interoperability solution "Solution notification to propose changes", with the following motivation: Please, check my updates. |

    # Template 11. The moderation team approves a deletion request.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to approve deletion" solution
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                          |
      | subject   | Joinup: Your deletion request has been approved                                                                                                                       |
      | body      | You recently requested that the interoperability solution Solution notification to approve deletion be deleted. Your request was accepted by The Joinup Support Team. |

    # Template 12. The moderation team rejects the deletion.
    And all e-mails have been sent
    And I go to the homepage of the "Solution notification to reject deletion" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    # Motivation required.
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "I don't feel like deleting this solution"
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                                                                          |
      | subject   | Joinup: Your request to delete your solution has been rejected                                                                                                                                                        |
      | body      | You recently requested that the interoperability solution Solution notification to reject deletion be deleted. Your request was rejected by The Joinup Support Team, due to I don't feel like deleting this solution. |

    # Template 13. The moderation team blacklists a solution.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to blacklist" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Blacklist"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                 |
      | subject   | Joinup: Your interoperability solution is blacklisted                                                                                                        |
      | body      | the moderator has blacklisted your interoperability solution - Solution notification to blacklist, you can contact the moderation team to resolve the issue. |

    # Template 14. The moderation team restores a solution from blacklisted.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to publish from blacklisted" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                              |
      | subject   | Joinup: Your interoperability solution is published again                                                                                 |
      | body      | the moderator has published back your interoperability solution - Solution notification to publish from blacklisted that was blacklisted. |

    # Template 15. The moderation team requests changes.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to request changes" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Request changes"
    # Motivation required.
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Can you change this?"
    And I press "Request changes"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                              |
      | subject   | Joinup: You are requested to update your solution                                                                                                                         |
      | body      | the moderator has requested you to modify the interoperability solution - Solution notification to request changes following the following advises: Can you change this?. |

    # Template 18. The moderation team deletes a solution without prior request.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to delete" solution
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                      |
      | subject   | Joinup: Your solution has been deleted by the moderation team                                     |
      | body      | The Joinup moderation team deleted the interoperability solution Solution notification to delete. |

    When I am logged in as "Ramiro Myers"

    # Template 18. The owner proposes changes.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to propose from request changes" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the following email should have been sent:
      | recipient | Pat Harper                                                                                                                               |
      | subject   | Joinup: An update of a solution has been proposed                                                                                        |
      | body      | Ramiro Myers has proposed an update of the Interoperability solution: "Solution notification to propose from request changes" on Joinup. |

    # Template 10. The owner requests a deletion.
    When all e-mails have been sent
    And I go to the homepage of the "Solution notification to request deletion" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Request deletion"
    Then the following email should have been sent:
      | recipient | Pat Harper                                                                                                                                              |
      | subject   | Joinup: A solution deletion has been requested                                                                                                          |
      | body      | Ramiro Myers requested that the Solution notification to request deletion interoperability solution, part of Collection of random solutions be deleted. |
