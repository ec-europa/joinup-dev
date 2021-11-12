@api @group-g
Feature: Prefill contact form fields
  In order to simplify the gathering of feedback from the users
  As a moderator
  I want to be able to prefill some fields of the contact form

  Scenario: The subject field can be prefilled through links.
    Given collection:
      | title | CAMSS test community |
      | state | validated            |

    When I am logged in as a facilitator of the "CAMSS test community" collection
    When I go to the homepage of the "CAMSS test community" collection
    And I open the plus button menu
    And I click "Add custom page"
    Then I should see the heading "Add custom page"

    # Create a page with a link that points to the contact form with the "subject" parameter containing some text.
    When I fill in the following:
      | Title | Change management                                                                    |
      | Body  | <p>Follow this <a href="../../contact?subject=CAMSS%20Change%20Request">link</a></p> |
    And I press "Save"
    Then I should see the heading "Change management"

    When I click "link"
    Then I should see the heading "Contact"
    And the "Subject" field should contain "CAMSS Change Request"
