@api
Feature: Solutions message overview
  As a moderator of the site
  In order to be able to manage incoming requests
  I need to be able to list messages that are sent throughout the website.

  Scenario: Only privileged users can access the report.
    When I am logged in as a user with the "authenticated" role
    And I go to "/admin/content/messages"
    Then the response status code should be 403

    When I am logged in as a moderator
    And I am on the homepage
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Messages overview"
    Then the response status code should be 200

  Scenario: Contact form messages are available in the message overview.
    And I am not logged in
    When I am on the homepage
    And I click "Contact Joinup Support" in the "Footer" region
    Then I should see the heading "Contact"

    When I fill in the following:
      | First name     | Leuteris                                |
      | Last name      | Papastamatakis                          |
      | Organisation   | Eko oil industries                      |
      | E-mail address | l.papastamatak@example.com              |
      | Category       | other                                   |
      | Subject        | Ran out of gas                          |
      | Message        | Do you have 1 euro to buy a cheese pie? |
    # We need to wait 5 seconds for the spam protection time limit to pass.
    And I wait for the spam protection time limit to pass
    And I press "Submit"
    Then I should see the success message "Your message has been submitted. Thank you for your feedback."

    When I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Messages overview"
    Then I should see the following lines of text:
      | Contact form submission                 |
      | Leuteris                                |
      | Papastamatakis                          |
      | Eko oil industries                      |
      | l.papastamatak@example.com              |
      | Ran out of gas                          |
      | Do you have 1 euro to buy a cheese pie? |

  @terms
  Scenario: Workflow messages are available in the message overview.
    Given owner:
      | name                 | type    |
      | Organisation example | Company |
    And I am logged in as an "authenticated user"
    When I go to the propose collection form
    When I fill in the following:
      | Title                 | Message overview proposal |
      | Description           | Doesn't matter.           |
      | Geographical coverage | Belgium                   |
    When I select "HR" from "Topic"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I fill in the following:
      | E-mail | invisible.man@example.com |
      | Name   | Invisible Man             |
    And I press "Create contact information"
    And I press "Propose"
    And I should see the heading "Message overview proposal"

    # Verify the message as a moderator.
    When I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Messages overview"
    Then I should see the following lines of text:
      | User proposed collection Message overview proposal                                 |
      | has proposed collection "Message overview proposal".                               |
      | To approve or reject this proposal, please go to                                   |
      | You'll be able to provide feedback.                                                |
      | The requestor will be notified of your decision and feedback.                      |
      | If you think this action is not clear or not due, please contact Joinup Support at |

    # Clean up the collection that was created.
    Then I delete the "Message overview proposal" collection
