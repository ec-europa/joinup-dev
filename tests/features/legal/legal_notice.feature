@api
Feature:
  - As a visitor, in order to register to Joinup, I have to accept the site's
    'Legal notice', otherwise I cannot register.
  - As registered user, in order to login when a new version of 'Legal notice'
    has been released, I have to accept the new version, otherwise I cannot
    login.
  - As a moderator, I want to be able to release new versions of 'Legal notice'
    or to edit the existing ones.

  Background:

    Given the following legal document version:
      | Document     | Label        | Version | Published | Acceptance label                                                                                   | Content                                                    |
      | Legal notice | Legal notice | 1.1     | yes       | I have read and accept the <a href="[entity_legal_document:url]">[entity_legal_document:label]</a> | The information on this site is subject to a disclaimer... |

  Scenario: User registration.

    Given I am on the homepage

    When I click "Legal notice" in the Footer region
    Then I should see the heading "Legal notice"
    And I should see "The information on this site is subject to a disclaimer..."

    When I click "Sign in"
    And I click "Create new account"

    Given I fill in "Email" with "dewan@example.com"
    And I fill in "Username" with "dewan"
    And I fill in "First name" with "Jo"
    And I fill in "Family name" with "de Wan"

    But I wait for the honeypot time limit to pass

    When I press "Create new account"
    Then I should see the error message "I have read and accept the "

    Given I check "I have read and accept the Legal notice"
    And I press "Create new account"

    Then I should see the success message "Thank you for applying for an account."

    # Check again that the link is accessible in the footer.
    When I click "Legal notice" in the Footer region
    Then I should see the heading "Legal notice"
    And I should see "The information on this site is subject to a disclaimer..."

    # As the user is created via UI, we should explicitly delete it.
    Given I delete the "dewan" user

  Scenario: User login when a new 'Legal notice' version is released.

    Given user:
      | Username | Rick    |
      | Password | secretz |

    When I am on the homepage
    And I click "Sign in"

    And I fill in "Email or username" with "Rick"
    And I fill in "Password" with "secretz"

    When I press "Sign in"
    Then I should see the warning message "You must accept this agreement before continuing."
    And I should see the heading "Legal notice"
    And I should see "The information on this site is subject to a disclaimer..."

    # Try to navigate.
    When I click "My account"
    Then I should see the warning message "You must accept this agreement before continuing."

    # Sign-out is allowed.
    When I click "Sign out"
    Then I should not see the warning message "You must accept this agreement before continuing."
    And I should see the link "Sign in"

    And I click "Sign in"

    And I fill in "Email or username" with "Rick"
    And I fill in "Password" with "secretz"

    When I press "Sign in"
    Then I should see the warning message "You must accept this agreement before continuing."
    And I should see the heading "Legal notice"
    And I should see "The information on this site is subject to a disclaimer..."

    Given I check "I have read and accept the Legal notice"
    And I press "Submit"
    Then I should see the heading "Rick"

    # Login again to check that the acceptance enforcement has gone.
    Given I click "Sign out"
    And I go to homepage
    And I click "Sign in"
    And I fill in "Email or username" with "Rick"
    And I fill in "Password" with "secretz"

    When I press "Sign in"
    Then I should not see the warning message "You must accept this agreement before continuing."
    When I click "My account"
    Then I should see the heading "Rick"

    # While Rick navigates on the site a new version is created and published.
    Given the following legal document versions:
      | Document     | Label        | Version | Published | Acceptance label    | Content     |
      | Legal notice | Legal notice | 2.0     | no        | Accept Version 2.0! | Version 2.0 |
    And the version "2.0" of "Legal notice" legal document is published

    When I go to homepage
    Then I should see the warning message "You must accept this agreement before continuing."
    And I should see the heading "Legal notice"
    And I should see "Version 2.0"
    And I should see "Accept Version 2.0!"

    When I check "Accept Version 2.0!"
    And I press "Submit"
    Then I should not see the warning message "You must accept this agreement before continuing."

  Scenario: Moderator tasks.

    Given I am logged in as a moderator
    When I click "Legal"
    Then I should see the heading "Legal notice versions"
    And I should see the link "New version"
    And the row "Legal notice" is selected

    When I press "Set published version"
    Then I should see the success message "No changes have been made."

    When I click "Edit"
    Then I should see the heading "Edit Legal notice"

    Given I fill in "Title" with "Legal notice (changed)"
    And I fill in "Version" with "1.1.1"
    And I fill in "Acceptance label" with "Accept!"

    When I press "Save"
    Then I should see "Legal notice (changed)"
    And I should see "1.1.1"
    And the row "Legal notice (changed)" is selected

    Given I click "New version"
    And I fill in "Title" with "The new legal notice"
    And I fill in "Version" with "2.0"
    And I fill in "Content" with "New rules..."
    And I fill in "Acceptance label" with "Accept 2.0!"

    When I press "Save"
    Then the row "Legal notice (changed)" is selected
    But the row "The new legal notice" is not selected

    When I select the "The new legal notice" row
    And I press "Set published version"
    Then I should see the success message "The new legal notice 2.0 has been published."
    And the row "The new legal notice" is selected
    But the row "Legal notice (changed)" is not selected

    When I click "Legal notice" in the Footer region
    Then I should see the heading "The new legal notice"
    And I should see "New rules..."

    # As this version has been created via UI it should be manually deleted.
    And I delete the version "The new legal notice" of document "Legal notice"
