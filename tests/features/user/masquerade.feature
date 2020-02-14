@api
Feature:
  - As a moderator, granted with 'edit masquerade field' permission, in order to
    allow several users to masquerade, I am able to edit the 'Masquerade as'
    field on their profile edit form and set accounts that this user is able to
    masquerade as.
  - As moderator, I can see the the 'Masquerade as' link list on a user profile
    page but clicking on any link will redirect me to the target user profile.
  - As a user able to masquerade, when I click on a target account link in my
    profile page, I'm masquerading as that user. If that user can masquerade, I
    cannot do it once more as I'm already masquerading.

  Scenario: Test access on 'Masquerade as' field in the profile edit form.

    Given users:
      | Username             | First name | Family name |
      | i_want_to_masquerade | Woz        | Dizzy       |
      | target_account_1     |            |             |
      | target_account_2     |            |             |
    And I am logged in as a moderator

    When I visit the profile of i_want_to_masquerade
    And I click Edit
    Then the following fields should be present "Masquerade as"

    When I fill in "masquerade_as[0][target_id]" with "target_account_1"
    And I press "Save"
    And I click Edit

    When I fill in "masquerade_as[1][target_id]" with "target_account_2"
    And I press "Save"
    And I visit the profile of i_want_to_masquerade
    Then I should see the following links:
      | target_account_1 |
      | target_account_2 |

    # Check the /admin/people view column.
    When I click "People"
    Then I should see the text "target_account_1, target_account_2" in the "Woz Dizzy" row

    # A user without 'edit masquerade field' permission cannot edit the field.
    Given I am logged in as "i_want_to_masquerade"
    When I visit the profile of i_want_to_masquerade
    And I click Edit
    Then the following fields should not be present "Masquerade as"

  Scenario: Check who can see the 'Masquerade as' field.

    Given users:
      | Username             | First name | Family name | Masquerade as                     |
      | target_account_1     |            |             |                                   |
      | target_account_2     |            |             | target_account_1                  |
      | i_want_to_masquerade | Woz        | Dizzy       | target_account_1,target_account_2 |

    # As a moderator, you can access the 'Masquerade as' field of another user.
    When I am logged in as a moderator
    And I visit the profile of i_want_to_masquerade
    Then I should see "Masquerade as"
    And I should see the following links:
      | target_account_1 |
      | target_account_2 |

    When I click target_account_1
    Then I should be on the "target_account_1" user profile page
    But I move backward one page
    When I click target_account_2
    Then I should be on the "target_account_2" user profile page

    # As a regular user I am able to see the accounts that I can masquerade as.
    When I am logged in as i_want_to_masquerade
    And I visit the profile of i_want_to_masquerade
    Then I should see "Masquerade as"
    And I should see the following links:
      | target_account_1 |
      | target_account_2 |

    When I click target_account_1
    Then I should see the success message "You are now masquerading as target_account_1."

    When I click Unmasquerade
    Then I should see the success message "You are no longer masquerading as target_account_1."

    When I click "My account"
    And I click target_account_2
    Then I should see the success message "You are now masquerading as target_account_2."

    # Now "I am target_account_2"
    When I click "My account"
    Then I should see "Masquerade as"
    And I should see the link "target_account_1"

    # target_account_2 is able to masquerade as target_account_1 but as is
    # already masquerading, they cannot do it once more, so the link points only
    # to the target_account_1 profile page.
    When I click "target_account_1"
    Then I should be on the "target_account_1" user profile page

    When I click Unmasquerade
    Then I should see the success message "You are no longer masquerading as target_account_2."

    When I click "My account"
    Then I should be on the "i_want_to_masquerade" user profile page
