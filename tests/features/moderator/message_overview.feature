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

  Scenario: Find messages that have been sent through the contact form.
    Given I am not logged in
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
