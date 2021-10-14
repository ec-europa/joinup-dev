# Tests notifications for solutions. This file does not include template 1.
# Template 1 is tested in the add_solution.feature already.
@api @terms @group-f
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
      | Username     | Roles     | First name | Family name | E-mail                   |
      | Pat Harper   | moderator | Pat        | Harper      | pat.harper@example.com   |
      | Ramiro Myers |           | Ramiro     | Myers       | ramiro.myers@example.com |
      | Edith Poole  |           | Edith      | Poole       | edith.poole@example.com  |
    And the following solutions:
      | title                                                 | author       | description | logo     | banner     | owner              | contact information | state        | topic       | solution type | collection                     |
      | Solution notification to propose changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated    | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to blacklist                    | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated    | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to publish from blacklisted     | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | blacklisted  | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to request changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated    | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to propose from request changes | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | needs update | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to delete by moderator team     | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated    | E-inclusion | Business      | Collection of random solutions |
      | Solution notification to delete by owner              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated    | E-inclusion | Business      | Collection of random solutions |

    When I am logged in as "Pat Harper"

    # Template 7. The moderation team proposes changes.
    And I go to the homepage of the "Solution notification to propose changes" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    # Motivation required.
    Then I should see the error message "This action requires you to fill in the motivation field"
    When I fill in "Motivation" with "Please, check my updates"
    And I press "Propose"
    Then the email sent to "Ramiro Myers" with subject "Joinup: Changes have been proposed for your solution" contains the following lines of text:
      | text                                                                                                                                                                                   |
      | The Joinup Support Team has requested you to modify the interoperability solution "Solution notification to propose changes", with the following motivation: Please, check my updates. |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                                     |

    # Template 13. The moderation team blacklists a solution.
    When I go to the homepage of the "Solution notification to blacklist" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Blacklist"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                                                 |
      | subject   | Joinup: Your interoperability solution is blacklisted                                                                                                        |
      | body      | the moderator has blacklisted your interoperability solution - Solution notification to blacklist, you can contact the moderation team to resolve the issue. |

    # Template 14. The moderation team restores a solution from blacklisted.
    When I go to the homepage of the "Solution notification to publish from blacklisted" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                                              |
      | subject   | Joinup: Your interoperability solution is published again                                                                                 |
      | body      | the moderator has published back your interoperability solution - Solution notification to publish from blacklisted that was blacklisted. |

    # Template 15. The moderation team requests changes.
    When I go to the homepage of the "Solution notification to request changes" solution
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
      | bcc       | pat.harper@example.com                                                                                                                                                    |
    And the email sent to "Ramiro Myers" with subject "Joinup: You are requested to update your solution" contains the following lines of text:
      | text                                                                                                                                                                      |
      | the moderator has requested you to modify the interoperability solution - Solution notification to request changes following the following advises: Can you change this?. |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                                                        |

    # Template 18. The moderation team deletes a solution without prior request.
    When I go to the homepage of the "Solution notification to delete by moderator team" solution
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then the following email should have been sent:
      | recipient | Ramiro Myers                                                                                                        |
      | subject   | Joinup: Your solution has been deleted by the moderation team                                                       |
      | body      | The Joinup moderation team deleted the interoperability solution Solution notification to delete by moderator team. |

    When I am logged in as "Ramiro Myers"

    # Template 18. The owner proposes changes.
    When I go to the homepage of the "Solution notification to propose from request changes" solution
    And I click "Edit" in the "Entity actions" region
    And I press "Propose"
    Then the email sent to "Pat Harper" with subject "Joinup: An update of a solution has been proposed" contains the following lines of text:
      | text                                                                                                                                     |
      | Ramiro Myers has proposed an update of the Interoperability solution: "Solution notification to propose from request changes" on Joinup. |
      | If you think this action is not clear or not due, please contact Joinup Support at                                                       |

    # The owner deletes their own solution. No email should be sent to the owner
    # since we do not send notifications to the actor.
    When I mark all emails as read
    And I go to the homepage of the "Solution notification to delete by owner" solution
    And I click "Edit" in the "Entity actions" region
    And I click "Delete"
    And I press "Delete"
    Then 0 e-mails should have been sent
