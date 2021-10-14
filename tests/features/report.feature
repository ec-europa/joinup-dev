@api @group-g
Feature: Report inappropriate content
  In order to outsource the discovery of inappropriate content on the site
  As a moderator
  I want to provide a "Report" button on community content

  Scenario Outline: Report inappropriate community content
    Given collection:
      | title | Deployment tools |
      | state | validated        |
    And "<type>" content:
      | title   | body   | collection       | state     |
      | <title> | <body> | Deployment tools | validated |
    Given I am not logged in
    When I go to the content page of the type "<type>" with the title "<title>"
    And I click "Report"
    Then I should see the heading "Contact"
    And the "Category" field should contain "report"
    And the "Page URL" field should contain the link to the "<title>" page

    # Submit the form to check if 'destination' has been correctly set.
    And I fill in the following:
      | First name     | John            |
      | Last name      | Doe             |
      | Organisation   |                 |
      | E-mail address | doe@example.rg  |
      | Subject        | Invalid content |
      | Message        | Spam            |
    # We need to wait 5 seconds for the spam protection time limit to pass.
    And I wait for the spam protection time limit to pass
    When I press "Submit"
    Then I should see the heading "<title>"

    Examples:
      | type        | title                        | body                    |
      | discussion  | Git is not a deployment tool | Use tarballs to deploy. |
      | custom_page | The best tools               | Current best-in-class.  |
      | news        | Now deploying to containers  | Long awaited feature.   |
      | document    | Deployment strategies        | Deploy faster.          |
      | event       | GovDeploy Bootcamp 2017      | Submit your session.    |
