@api
Feature: Submit the contact form
  In order to gather feedback from the users
  As a moderator
  I want to collect wishes and grievances through a contact form

  @email
  Scenario: Submit a message as an anonymous user
    Given users:
      | Username                | Roles     |
      | Valentína Řezník        | moderator |
      | Oluwakanyinsola Opeyemi | moderator |

    # There should be a link to the contact form in the footer.
    Given I am not logged in
    When I am on the homepage
    And I click "Contact" in the "Footer" region
    Then I should see the heading "Contact"
    # The honeypot field that needs to be empty on submission.
    Then the following fields should be present "user_homepage"

    When I fill in the following:
      | First name     | Oswine                      |
      | Last name      | Wulfric                     |
      | Organisation   | The Deaf-Mute Society       |
      | E-mail address | oswine@example.za           |
      | Category       | other                       |
      | Subject        | Screen reader accessibility |
      | Message        | Dear sir, madam, ...        |
      | Attachment     | logo.jpg                    |
    # We need to wait 5 seconds for the honeypot validation to pass.
    Then I wait for the honeypot validation to pass
    And I press "Submit"

    # Both moderators should have received the notification e-mail.
    Then the following email should have been sent:
      | template  | Contact form submission          |
      | recipient | Valentína Řezník                 |
      | subject   | Joinup - Contact form submission |
      | body      | Dear sir, madam, ...             |
    And the following email should have been sent:
      | template  | Contact form submission          |
      | recipient | Oluwakanyinsola Opeyemi          |
      | subject   | Joinup - Contact form submission |
      | body      | Dear sir, madam, ...             |

  Scenario: Check required fields
    When I am on the contact form
    And I press "Submit"
    Then I should see the following error messages:
      | error messages                    |
      | E-mail address field is required. |
      | First name field is required.     |
      | Last name field is required.      |
      | Category field is required.       |
      | Message field is required.        |
      | Subject field is required.        |

  Scenario: Credentials of an authenticated user are prefilled
    Given user:
      | Username    | Wally Papamichael           |
      | First name  | Wally                       |
      | Family name | Papamichael                 |
      | E-mail      | w.papamichael@schiffmann.de |
    Given I am logged in as "Wally Papamichael"
    When I am on the contact form
    Then the "First name" field should contain "Wally"
    And the "Last name" field should contain "Papamichael"
    And the "E-mail address" field should contain "w.papamichael@schiffmann.de"
