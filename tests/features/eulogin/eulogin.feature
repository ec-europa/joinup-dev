@api @casMockServer
Feature: Log in through EU Login
  In order to access all website of the European Commission with the same credentials
  As a user with an existing EU Login account
  I need to be able to register and log in to Joinup using EU Login

  Scenario: A local account is auto-registered on user choice.
    Given CAS users:
      | Username    | E-mail                         | Password  | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu | Qwerty098 | Chuck      | Norris    |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"

    # The user gets redirected to the CAS server.
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "texasranger@chucknorris.com.eu"
    And I fill in "Password" with "wrong password"
    And I press the "Log in" button
    Then I should see the error message "Unrecognized user name or password."

    When I fill in "Password" with "Qwerty098"
    And I press the "Log in" button

    # The user gets redirected back to Drupal.
    Then I should see the heading "Already a Joinup user?"
    And I should see "Since you have signed in for the first time using EU Login, you need to take one extra step."
    And I should see "Before you make your selection below, please note this important information:"
    And I should see "If you are an existing user on Joinup, and would like to keep all your account data (collection/solution memberships, published events, news, documents, discussions etc.), we suggest you select the first option to pair your existing account with your EU Login account;"
    And I should see "If you are a new user on Joinup, the second option is the right one for you."
    And I should see "Please make your selection:"
    And I should see "I am an existing user (pair my existing account with my EU Login account)"
    And I should see "You will be asked to login with your site credentials."
    And I should see "I am a new user (create a new account)"
    And I should see "No action is required on your side. A new account, linked to your EU Login account, will be created."

    Given I select the radio button "I am a new user (create a new account)"
    When I press "Next"
    Then I should see the success message "Fill in the fields below to let the Joinup community learn more about you!"

    # The user has been redirected to its user account edit form.
    Then the following fields should be present "Email, First name, Family name, Photo, Country of origin, Professional domain, Business title"
    And the following fields should be present "Facebook, Twitter, LinkedIn, GitHub, SlideShare, Youtube, Vimeo"
    But I should not see "Fail - Password length must be at least 8 characters."
    And I should not see "Password character length of at least 8"
    And I should not see "Fail - Password must contain at least 3 types of characters from the following character types: lowercase letters, uppercase letters, digits, special characters."
    And I should not see "Minimum password character types: 3"

    And the user chucknorris should have the following data in their user profile:
      | First name  | Chuck  |
      | Family name | Norris |

  Scenario: An existing local account can be linked by the user.
    Given CAS users:
      | Username    | E-mail                         | Password  | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu | Qwerty098 | Chuck      | Norris    |
    And users:
      | Username             | Password | E-mail                           | First name | Family name | Organisation |
      | chuck_the_local_hero | 12345    | chuck_the_local_hero@example.com | LocalChick | LocalNorris | ACME         |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"

    # The user gets redirected to the CAS server.
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "texasranger@chucknorris.com.eu"
    When I fill in "Password" with "Qwerty098"
    And I press the "Log in" button

    # The user gets redirected back to Drupal.
    Then I should see the heading "Already a Joinup user?"
    Given I select the radio button "I am an existing user (pair my existing account with my EU Login account)"

    # Try post the form with incomplete data.
    When I press "Sign in"
    Then I should see the following error messages:
      | error messages              |
      | Username field is required. |
      | Password field is required. |

    # Try to post with wrong credentials.
    Given I fill in "Email or username" with "chuck_the_local_hero"
    And I fill in "Password" with "wrong..."
    When I press "Sign in"
    Then I should see the error message "Unrecognized username or password. Forgot your password?"

    # Successful login.
    Given I fill in "Email or username" with "chuck_the_local_hero"
    And I fill in "Password" with "12345"
    When I press "Sign in"
    Then I should see the success message "Your EU Login account chucknorris has been successfully linked to your local account Chuck Norris."

    # The profile entries are overwritten, except the username & the email.
    And the user chuck_the_local_hero should have the following data in their user profile:
      | Username    | chuck_the_local_hero             |
      | E-mail      | chuck_the_local_hero@example.com |
      | First name  | Chuck                            |
      | Family name | Norris                           |

  Scenario: An existing local account can be linked by the user using the email.
    Given CAS users:
      | Username    | E-mail                         | Password  | First name | Last name |
      | chucknorris | texasranger@chucknorris.com.eu | Qwerty098 | Chuck      | Norris    |
    And users:
      | Username             | Password | E-mail                           | First name | Family name | Organisation |
      | chuck_the_local_hero | 12345    | chuck_the_local_hero@example.com | LocalChick | LocalNorris | ACME         |

    Given I visit "/cas"
    And I fill in "E-mail address" with "texasranger@chucknorris.com.eu"
    And I fill in "Password" with "Qwerty098"
    When I press the "Log in" button
    Then I should see the heading "Already a Joinup user?"
    Given I select the radio button "I am an existing user (pair my existing account with my EU Login account)"

    And I fill in "Email or username" with "chuck_the_local_hero@example.com"
    And I fill in "Password" with "12345"
    When I press "Sign in"
    Then I should see the success message "Your EU Login account chucknorris has been successfully linked to your local account Chuck Norris."

    # The profile entries are overwritten, except the username & the email.
    And the user chuck_the_local_hero should have the following data in their user profile:
      | Username    | chuck_the_local_hero             |
      | E-mail      | chuck_the_local_hero@example.com |
      | First name  | Chuck                            |
      | Family name | Norris                           |

  Scenario: An existing user can log in through EU Login
    Given users:
      | Username    | E-mail           | Password | First name | Family name |
      | jb007_local | 007-local@mi6.eu | 123      | JJaammeess | BBoonndd    |
    Given CAS users:
      | Username | E-mail     | Password           | First name | Last name | Local username |
      | jb007    | 007@mi6.eu | shaken_not_stirred | James      | Bond      | jb007_local    |

    # Try to login using the Drupal login form.
    Given I go to "/user/login"
    And I fill in "Email or username" with "jb007_local"
    And I fill in "Password" with "123"
    And I wait for the honeypot time limit to pass
    When I press "Sign in"
    Then I should see the error message "Please sign in with your EU Login account."

    # Test the password reset customized message as anonymous.
    Given I visit "/user/password"
    And I fill in "Email" with "007-local@mi6.eu"
    And I wait for the honeypot time limit to pass
    And I press "Submit"
    Then I should see the error message "The requested account is associated with EU Login and its password cannot be managed from this website."
    And I should see the link "EU Login"

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    Then I should see the heading "Sign in to continue"
    When I fill in "E-mail address" with "007@mi6.eu"
    When I fill in "Password" with "shaken_not_stirred"
    And I press the "Log in" button

    Then I should see the success message "You have been logged in."

    # The profile entries are overwritten, except the username & the email.
    And the user jb007_local should have the following data in their user profile:
      | Username    | jb007_local      |
      | E-mail      | 007-local@mi6.eu |
      | First name  | James            |
      | Family name | Bond             |

    # Test the customized message as logged in user.
    Given I visit "/user/password"
    And I wait for the honeypot time limit to pass
    And I press "Submit"
    Then I should see the error message "The requested account is associated with EU Login and its password cannot be managed from this website."
    And I should see the link "EU Login"

  Scenario: Fields imported from EU Login cannot be edited locally.
    Given users:
      | Username            | E-mail        |
      | full_cas_profile    | f@example.com |
      | partial_cas_profile | p@example.com |
      | no_cas_profile      | n@example.com |
      | without_cas         | w@example.com |

    Given CAS users:
      | Username            | E-mail        | Password | First name | Last name | Local username      |
      | full_cas_profile    | f@example.com | 123      | Joe        | Doe       | full_cas_profile    |
      | partial_cas_profile | p@example.com | 123      |            | Roe       | partial_cas_profile |
      | no_cas_profile      | n@example.com | 123      |            |           | no_cas_profile      |

    # User with full profile data.
    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    When I fill in "E-mail address" with "f@example.com"
    When I fill in "Password" with "123"
    And I press the "Log in" button
    And I click "My account"

    When I click "Edit"
    Then the "First name" field should contain "Joe"
    And the "Family name" field should contain "Doe"
    And the following fields should be disabled "First name,Family name"
    But I should not see "Username"
    And I should not see "full_cas_profile"
    And I should see the following lines of text:
      | Eu Login Account information                                                                                                                                                                                                       |
      | Your name and E-mail data is inherited from EU Login. To update this information, you can visit your EU Login account page here. Synchronisation will take a few minutes and it will be visible the next time you login on Joinup. |
      | Your e-mail address is not made public. We will only send you necessary system notifications and you can opt in later if you wish to receive additional notifications about content you are subscribed to.                         |
      | Your first name is publicly visible.                                                                                                                                                                                               |
      | Your last name is publicly visible.                                                                                                                                                                                                |

    When I press "Save"
    Then I should see the success message "The changes have been saved."

    # User with partial profile data.
    Given I am an anonymous user
    And I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    When I fill in "E-mail address" with "p@example.com"
    When I fill in "Password" with "123"
    And I press the "Log in" button
    And I click "My account"

    When I click "Edit"
    Then the "First name" field should contain ""
    And the "Family name" field should contain "Roe"
    And the following fields should not be disabled "First name"
    And the following fields should be disabled "Family name"
    But I should not see "Username"
    And I should not see "partial_cas_profile"
    And I should see "Your name and E-mail data is inherited from EU Login. To update this information, you can visit your EU Login account page here. Synchronisation will take a few minutes and it will be visible the next time you login on Joinup."
    But I should not see "Fail - Password length must be at least 8 characters."
    And I should not see "Password character length of at least 8"
    And I should not see "Fail - Password must contain at least 3 types of characters from the following character types: lowercase letters, uppercase letters, digits, special characters."
    And I should not see "Minimum password character types: 3"

    When I press "Save"
    Then I should see the error message "First name field is required."
    But I should not see the error message "Family name field is required."

    # User with no profile data.
    Given I am an anonymous user
    And I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    When I fill in "E-mail address" with "n@example.com"
    When I fill in "Password" with "123"
    And I press the "Log in" button
    And I click "My account"

    When I click "Edit"
    Then the "First name" field should contain ""
    And the "Family name" field should contain ""
    And the following fields should not be disabled "First name,Family name"
    But I should not see "Username"
    # The username appears in the page header because this use has no first and
    # last name. But we check the absence of "Username" and this is enough.
    And I should see "Your name and E-mail data is inherited from EU Login. To update this information, you can visit your EU Login account page here. Synchronisation will take a few minutes and it will be visible the next time you login on Joinup."
    But I should not see "Fail - Password length must be at least 8 characters."
    And I should not see "Password character length of at least 8"
    And I should not see "Fail - Password must contain at least 3 types of characters from the following character types: lowercase letters, uppercase letters, digits, special characters."
    And I should not see "Minimum password character types: 3"

    When I press "Save"
    Then I should see the error message "First name field is required."
    And I should see the error message "Family name field is required."

    # User not linked to a CAS account.
    Given I am logged in as "without_cas"
    And I click "My account"

    When I click "Edit"
    Then the "First name" field should contain ""
    And the "Family name" field should contain ""
    And the following fields should not be disabled "First name,Family name"
    And I should see "Username"
    And I should see "without_cas"
    And I should see "A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email."
    And I should see "Fail - Password length must be at least 8 characters."
    And I should see "Password character length of at least 8"
    And I should see "Fail - Password must contain at least 3 types of characters from the following character types: lowercase letters, uppercase letters, digits, special characters."
    And I should see "Minimum password character types: 3"

    When I press "Save"
    Then I should see the error message "First name field is required."
    And I should see the error message "Family name field is required."

  Scenario: The Drupal login form shows a warning message.
    When I visit "/user/login"
    Then I should see the warning message "As of 01/02/2020, EU Login will be the only authentication method available on Joinup. So, we strongly recommend you to choose EU Login as your preferred sign-in method!"
    And I should see the link "EU Login"

  Scenario: A new user tries to register with an existing Email.
    Given users:
      | Username | E-mail          |
      | joe      | joe@example.com |

    And CAS users:
      | Username | E-mail          | Password |
      | joe_doe  | joe@example.com | 123      |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    When I fill in "E-mail address" with "joe@example.com"
    When I fill in "Password" with "123"
    And I press the "Log in" button

    Given I select the radio button "I am a new user (create a new account)"
    When I press "Next"

    Then I should see the following error messages:
      | error messages                                                                                             |
      | The email address joe@example.com is already taken.                                                        |
      | If you are the owner of this account please select the first option, otherwise contact the Joinup support. |

  Scenario: A new user tries to register with an existing username.
    Given users:
      | Username | E-mail          |
      | joe      | joe@example.com |

    And CAS users:
      | Username | E-mail              | Password |
      | joe      | joe.cas@example.com | 123      |

    Given I am on the homepage
    And I click "Sign in"
    When I click "EU Login"
    When I fill in "E-mail address" with "joe.cas@example.com"
    When I fill in "Password" with "123"
    And I press the "Log in" button

    When I select the radio button "I am a new user (create a new account)"
    When I press "Next"

    Then I should see the success message "Fill in the fields below to let the Joinup community learn more about you!"

  Scenario: The Drupal registration tab has been removed and the /user/register
  route redirects to EU Login registration form.
    When I visit "/user/login"
    Then I should not see the link "Create new account"
    When I visit "/user/register"
    Then the url should match "/cas/eim/external/register.cgi"

  Scenario: The CAS module 'Add CAS user(s)' functionality is dismantled.
    Given I am logged in as a moderator
    When I visit "/admin/people"
    Then I should not see the link "Add CAS user(s)"
    When I go to "/admin/people/create/cas-bulk"
    Then the response status code should be 404

  Scenario: A moderator is able to manually link a local user to its EU Login.
    Given user:
      | Username    | joe |
      | First name  | Joe |
      | Family name | Doe |

    And CAS users:
      | Username | E-mail          | Password | First name | Last name |
      | joe      | joe@example.com | 123      | Joe        | Doe       |

    Given I am logged in as a moderator
    And I click "People"
    When I click "Edit" in the "Joe Doe" row
    Then the "Allow user to log in via CAS" checkbox should not be checked

    Given I check "Allow user to log in via CAS"
    And I fill in "CAS Username" with "joe"
    When I press "Save"
    Then I should see the following success messages:
      | success messages                                                                |
      | An e-mail has been sent to the user to notify him on the change to his account. |
      | The changes have been saved.                                                    |

    When I click "Edit" in the "Joe Doe" row
    Then the "Allow user to log in via CAS" checkbox should be checked
    And the "CAS Username" field should contain "joe"

    Given I am an anonymous user
    And I am on the homepage
    And I click "Sign in"
    And I click "EU Login"
    And I fill in "E-mail address" with "joe@example.com"
    And I fill in "Password" with "123"
    When I press the "Log in" button
    Then I should see the success message "You have been logged in."
