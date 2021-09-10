@api
Feature:
  - As a visitor, in order to register to Joinup, I have to accept the site's
    'Legal notice', otherwise I cannot register.
  - As registered user, in order to login when a new version of 'Legal notice'
    has been released, I have to accept the new version, otherwise I cannot
    login.
  - As a moderator, I want to be able to release new versions of 'Legal notice'
    or to edit the existing ones.
  - As a visitor, in order to send a support request on the `/contact` page, I
    have to accept the site's 'Legal notice', otherwise I cannot submit.

  Background:
    Given the following legal document version:
      | Document     | Label | Published | Acceptance label                                                                                   | Content                                                    |
      | Legal notice | 1.1   | yes       | I have read and accept the <a href="[entity_legal_document:url]">[entity_legal_document:label]</a> | The information on this site is subject to a disclaimer... |

  Scenario: Anonymous user can read the Legal notice.
    Given I am on the homepage
    When I click "Legal notice" in the Footer region
    Then I should see the heading "Legal notice"
    And I should see "The information on this site is subject to a disclaimer..."

  Scenario: User login when a new 'Legal notice' version is released.
    Given CAS users:
      | Username | E-mail           | Password |
      | Rick     | rick@example.com | secretz  |

    And I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    And I fill in "E-mail address" with "rick@example.com"
    And I fill in "Password" with "secretz"
    And I press "Log in"

    And I select the radio button "I am a new user (create a new account)"
    Then I should see "I have read and accept the Legal notice"

    # Submit without accepting the 'Legal notice'.
    When I press "Next"
    Then I should see the error message "You must accept the Legal notice in order to use our platform."

    # After accepting the agreement the user can continue.
    Given I check "I have read and accept the Legal notice"
    And I press "Sign in"
    Then I should see the success message "Fill in the fields below to let the Joinup community learn more about you!"

    # The user has been redirected to its user account edit form.
    And the following fields should be present "Email, First name, Family name, Photo, Country of origin, Professional domain, Business title"
    And the following fields should be present "Facebook, Twitter, LinkedIn, GitHub, SlideShare, Youtube, Vimeo"

    # Login again to check that the acceptance enforcement has gone.
    Given I click "Sign out"
    And I go to homepage
    And I click "Sign in"
    And I click "EU Login"

    When I fill in "E-mail address" with "rick@example.com"
    And I fill in "Password" with "secretz"
    And I press "Log in"
    Then I should not see the warning message "You must accept this agreement before continuing."
    When I click "My account"
    Then I should see the heading "Rick"

    # While Rick navigates on the site a new version is created and published.
    Given the following legal document versions:
      | Document     | Label | Published | Acceptance label    | Content             |
      | Legal notice | 2.0   | no        | Accept Version 2.0! | Version 2.0 content |
    And the version "2.0" of "Legal notice" legal document is published

    When I go to homepage
    Then I should see the warning message "You must accept this agreement before continuing."
    And I should see the heading "Legal notice"
    And I should see "Version 2.0 content"
    And I should see "Accept Version 2.0!"

    # Try to navigate.
    When I click "My account"
    Then I should see the warning message "You must accept this agreement before continuing."

    # Sign-out is allowed.
    When I click "Sign out"
    Then I should not see the warning message "You must accept this agreement before continuing."
    And I should see the link "Sign in"

    And I click "Sign in"
    When I click "EU Login"
    And I fill in "E-mail address" with "rick@example.com"
    And I fill in "Password" with "secretz"

    When I press "Log in"

    Then I should see the warning message "You must accept this agreement before continuing."
    And I should see the heading "Legal notice"
    And I should see "Version 2.0 content"

    When I check "Accept Version 2.0!"
    And I press "Submit"
    Then I should not see the warning message "You must accept this agreement before continuing."

    # Clean up the user that was created manually during the scenario.
    Then I delete the "Rick" user

  Scenario: Moderator tasks.
    Given I am logged in as a moderator
    When I click "Legal"
    Then I should see the heading "Legal notice versions"
    And I should see the link "New version"
    And the row "1.1" is selected

    When I press "Set published version"
    Then I should see the success message "No changes have been made."

    When I click "Edit"
    Then I should see the heading "Edit 1.1"

    Given I fill in "Title" with "1.1.1"
    And I fill in "Acceptance label" with "Accept!"

    When I press "Save"
    Then I should see "1.1.1"
    And the row "1.1.1" is selected

    Given I click "New version"
    And I fill in "Title" with "v2.0"
    And I fill in "Document text" with "New rules..."
    And I fill in "Acceptance label" with "Accept 2.0!"

    When I press "Save"
    Then the row "1.1.1" is selected
    But the row "v2.0" is not selected

    When I select the "v2.0" row
    And I press "Set published version"
    Then I should see the success message "Legal notice v2.0 has been published."
    And the row "v2.0" is selected
    But the row "1.1.1" is not selected

    When I click "Legal notice" in the Footer region
    Then I should see the heading "Legal notice"
    And I should see "New rules..."

    # As this version has been created via UI it should be manually deleted.
    And I delete the version "v2.0" of document "Legal notice"

  Scenario: Anonymous using the support contact form.
    Given I am on "/contact"
    Then I should see "I have read and accept the Legal notice"
    And I should see "Before you submit your request check our FAQ section in case it covers your query/issue."

    Given I fill in "First name" with "Eleanor"
    And I fill in "Last name" with "Rigby"
    And I fill in "Organisation" with "Lonely People"
    And I fill in "E-mail address" with "depression@example.com"
    And I select "Legal issue" from "Category"
    And I fill in "Subject" with "All the lonely people where do they all come from?"
    And I fill in "Message" with "Father McKenzie, wiping the dirt / From his hands as he walks from the grave / No one was saved"
    Then I wait for the honeypot time limit to pass

    When I press "Submit"
    Then I should see the error message "You must accept the Legal notice in order to use our platform."

    But I check "I have read and accept the Legal notice"
    Then I wait for the honeypot time limit to pass
    When I press "Submit"
    Then I should see the success message "Your message has been submitted. Thank you for your feedback."

    Given I am logged in as an "authenticated user"
    And I accept the "Legal notice" agreement

    When I am on "/contact"
    Then I should not see "I have read and accept the Legal notice"
    And I should see "Before you submit your request check our FAQ section in case it covers your query/issue."

  Scenario: Legal notice redirect takes precedence over other redirects.
    Given users:
      | Username        |
      | Sergeant Pepper |
    And CAS users:
      | Username        | E-mail                  | Password | Local username  |
      | Sergeant Pepper | pepper@royalnavy.mod.uk | p3pp3r   | Sergeant Pepper |
    And collections:
      | title              | state     |
      | Land of submarines | validated |

    Given I am an anonymous user
    When I go to the homepage of the "Land of submarines" collection
    And I click "Join this collection"
    Then I should see the text "Sign in to join"

    # When an anonymous user is trying to join a collection, they are first
    # redirected to the login form, and then redirected back to the collection
    # so they can opt in to receive email notifications. However, if the user
    # has not yet accepted the current legal notice, they should be redirected
    # to the legal notice form first.
    Given the following legal document version:
      | Document     | Label | Published | Acceptance label    | Content             |
      | Legal notice | 2.0   | no        | Accept Version 2.0! | Version 2.0 content |
    And the version "2.0" of "Legal notice" legal document is published

    When I press "Sign in / Register"
    Then I should see the heading "Sign in to continue"

    When I fill in "E-mail address" with "pepper@royalnavy.mod.uk"
    And I fill in "Password" with "p3pp3r"
    And I press "Log in"
    Then I should see the heading "Legal notice"
    And I should see the warning message "You must accept this agreement before continuing."

    # After accepting the legal notice form we should finally be redirected to
    # the collection homepage.
    When I check "Accept Version 2.0!"
    And I press "Submit"
    Then I should see the heading "Land of submarines"

    # Quick check to see that the right messages are shown. The complete
    # workflow is described in "join-as-anonymous.feature".
    And I should see the success message "You have been logged in."
    And I should see the success message "You are now a member of Land of submarines."
