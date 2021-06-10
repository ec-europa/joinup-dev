@api @terms @email @group-a
Feature:
  In order to have the relevant users of the website up to date
  When an action is taken on a pledge
  I need to have the appropriate notifications send out.

  Background:
    Given the following collection:
      | title | Notifications challenge |
      | state | validated               |
    And the following solution:
      | title      | Email server            |
      | state      | validated               |
      | collection | Notifications challenge |
    And the following contact:
      | email       | proud-servers@example.com |
      | name        | Proud servers secretariat |
      | Company webpage | http://www.example.com    |
    And the following owner:
      | name       | type                  |
      | John Smith | Private Individual(s) |
    And users:
      | Username         | First name | Family name | E-mail                       | Roles     |
      | Pledge Owner     | Pledge     | Owner       | pledge.owner@example.com     |           |
      | Pledge Moderator | Pledge     | Moderator   | pledge.moderator@example.com | moderator |
      | Solution owner   | Solution   | Owner       | solution.owner@example.com   |           |
    And the following solution user membership:
      | solution     | user           | roles |
      | Email server | Solution owner | owner |

  Scenario: Notification to moderators when a pledge is directly proposed.
    When I am logged in as "Pledge Owner"
    And I go to the "Email server" solution
    And I click "Pledge" in the "Header" region
    And I fill in the following:
      | Title | Web server resources |
    And I fill in the following in the "Contact information" field:
      | Name   | John Smith       |
      | E-mail | test@example.com |
    # Click 'Add new' in the owner field.
    And I press "Add new"
    And I fill in the following in the "Owner" field:
      | Name | John Smith |
    And I select "Resources" from "Type of contribution"
    And I press "Submit for approval"
    Then I should see the heading "Web server resources"
    And the following email should have been sent:
      | recipient | Pledge Moderator                                                                    |
      | subject   | COVID-19 Challenge: A new pledge requires validation in the "Email server" solution |
      | body      | A pledge has been submitted for validation in the "Email server" solution.          |

    # Set as under validation.
    When I am logged in as "Pledge Moderator"
    And I go to the "Web server resources" pledge
    And I click "Edit" in the "Entity actions" region
    And I press "Set as under approval"
    Then I should see the heading "Web server resources"
    And the following email should have been sent:
      | recipient | Pledge Owner                                                                                                                                                                                       |
      | subject   | COVID-19 Challenge: Your pledge in "Email server" solution is under validation                                                                                                                     |
      | body      | A member of our staff has been assigned to verify the eligibility of your pledge and might contact you to complete the process. You will be notified for the conclusion of the validation process. |

    # Validate the pledge.
    Given I click "Edit" in the "Entity actions" region
    And I press "Validate"
    Then I should see the heading "Web server resources"
    And the following email should have been sent:
      | recipient | Pledge Owner                                                                                                                                         |
      | subject   | COVID-19 Challenge: Your pledge in "Email server" solution has been published                                                                        |
      | body      | We are pleased to inform you that your pledge in "Email server" solution has been checked by our staff and was found valid, thus has been published. |

    And the following email should have been sent:
      | recipient | Solution owner                                                                                                           |
      | subject   | COVID-19 Challenge: A new pledge has been published in your solution "Email server"                                      |
      | body      | We are pleased to inform you that a pledge has been published in your solution "Email server". To view the pledge, click |

    Then I delete the "John Smith" contact information
    And I delete the "John Smith" owner
