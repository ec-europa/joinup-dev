@api @group-a
Feature:
  In order to see how content evolved over time
  As a content author or facilitator
  I need to be able to see revisions of content

  Scenario Outline: Community content revisions
    Given <group type>:
      | title       | Hellow world                              |
      | description | Because I do not know how to spell Hello. |
      | moderation  | <moderation>                              |
      | state       | validated                                 |
    And users:
      | Username           | E-mail                    | Roles     |
      | Clumsy Bounce      | clumsy.bounce@example.com |           |
      | Authenticated Bump | auth_bump@yahoo.co.uk     |           |
      | Grumpy Author      | grumpy@auth.com           |           |
      | Jelly Facilitator  | fajelly@gmail.com         |           |
      | Fuzzy Moderator    | fuzzy_mod@fuzzample.org   | moderator |
    And <group type> user memberships:
      | <group type> | user              | roles       |
      | Hellow world | Clumsy Bounce     |             |
      | Hellow world | Grumpy author     | author      |
      | Hellow world | Jelly Facilitator | facilitator |
    And <content type> content:
      | title                | body                                   | state     | author        | <group type> |
      | Goodbi world         | Because I cannot spell Goodbye either. | validated | Clumsy Bounce | Hellow world |
      | Careful with that ax | Someone needs to ax these questions.   | validated | Grumpy Author | Hellow world |
    And <content type> revisions:
      | current title        | title                 |
      | Careful with that ax | Careful with that axe |

    # The revisions page does not appear for content that has only 1 revision.
    # We need at least 2 revisions since the main purpose of the revisions page
    # is to compare different revisions.
    Given I am an anonymous user
    When I visit the "Goodbi world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Sign in to continue"

    Given I am logged in as "Authenticated Bump"
    When I visit the "Goodbi world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    Given I am logged in as "Grumpy Author"
    When I visit the "Goodbi world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    Given I am logged in as "Fuzzy Moderator"
    When I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions" in the "Entity actions" region
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    Given I am logged in as "Jelly Facilitator"
    When I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions" in the "Entity actions" region
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    Given I am logged in as "Clumsy Bounce"
    When I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions"
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    # Create a second revision. This will unlock the revisions page. We need at
    # least 2 revisions since the page allows us to compare different revisions.
    Given <content type> revisions:
      | current title | title         | body                       |
      | Goodbi world  | Goodbye world | I found the spell checker. |

    Given I am an anonymous user
    When I visit the "Goodbye world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbye world"
    Then I should see the heading "Sign in to continue"

    Given I am logged in as "Authenticated Bump"
    When I visit the "Goodbye world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbye world"
    Then I should see the heading "Access denied"

    # The author of another piece of content should not have access.
    Given I am logged in as "Grumpy Author"
    When I visit the "Goodbye world" <content type>
    Then I should not see the "Entity actions" region
    When I visit the revisions page for "Goodbye world"
    Then I should see the heading "Access denied"

    Given I am logged in as "Fuzzy Moderator"
    When I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region
    When I visit the revisions page for "Goodbye world"
    Then I should see the heading "Revisions for Goodbye world"

    Given I am logged in as "Jelly Facilitator"
    When I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region
    When I visit the revisions page for "Goodbye world"
    Then I should see the heading "Revisions for Goodbye world"

    Given I am logged in as "Clumsy Bounce"
    When I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region

    # Verify that revisions can be compared.
    When I click "Revisions" in the "Entity actions" region
    Then I should see the heading "Revisions for Goodbye world"
    When I press "Compare selected revisions"
    Then I should see the heading "Changes to Goodbye world"
    And I should see the following lines of text:
      | Goodbi world                           |
      | Goodbye world                          |
      | Because I cannot spell Goodbye either. |
      | I found the spell checker              |

    # Verify that an author can revert a revision but not delete it.
    When I visit the revisions page for "Goodbye world"
    Then I should see the link "Revert"
    But I should not see the link "Delete" in the "Content" region
    When I click "Revert"
    And I press "Revert"
    # If the parent group is moderated, the "Goodbye title" title will be shown
    # here because the new version will be in "proposed" state which is not
    # published.
    Then I should see the heading "Revisions for <final title>"
    And the "<final title>" <content type> should have 3 revisions
    When I go to the "<final title>" <content type>
    Then I should see the heading "<final title>"

    # After leaving the group the user is no longer able to see revisions.
    When I click "<leave link>"
    And I press the "Confirm" button
    Then I should see the success message "You are no longer a member of Hellow world."

    When I visit the "<final title>" <content type>
    Then I should not see the link "Revisions"
    When I visit the revisions page for "<final title>"
    Then I should see the heading "Access denied"

    # If the user rejoins the group, access is restored.
    When I visit the "<final title>" <content type>
    And I press "<join button>"
    Then I should see the link "Revisions" in the "Entity actions" region

    # Check that a facilitator can approve the restoring of a revision that has
    # been proposed by a non-author. This part of the scenario only affects
    # moderated groups. For non-moderated groups this results in no change.
    Given I am logged in as "Fuzzy Moderator"
    When I visit the "<final title>" <content type>
    And I click "Edit" in the "Entity actions" region
    And I press "<moderate action>"
    Then I should see the heading "Goodbi world"

    # When a content author is blocked from the group (e.g. because they were
    # posting spam) then they should no longer have access to the revisions.
    When I go to the "Hellow world" <group type>
    And I click "Members" in the "Left sidebar"
    And I check the box "Update the member Clumsy Bounce"
    And I select "Block the selected membership(s)" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the following success messages:
      | success messages                                        |
      | Block the selected membership(s) was applied to 1 item. |

    Given I am logged in as "Clumsy Bounce"
    When I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions"
    When I visit the revisions page for "Goodbi world"
    Then I should see the heading "Access denied"

    # Users that have the 'Author' role can revert their content in moderated
    # groups without requiring facilitator approval.
    Given I am logged in as "Grumpy Author"
    When I visit the "Careful with that axe" <content type>
    And I click "Revisions" in the "Entity actions" region
    And I click "Revert"
    And I press "Revert"
    Then I should see the heading "Revisions for Careful with that ax"
    When I go to the "Careful with that ax" <content type>
    Then I should see the heading "Careful with that ax"

    # Note that discussions are always considered to be post-moderated even if
    # they are posted in a pre-moderated group. The reasoning behind this is
    # everyone should always be free to start a discussion. See ISAICP-2265.
    Examples:
      | group type | content type | moderation | final title   | moderate action | join button                | leave link                     |
      | collection | discussion   | yes        | Goodbi world  | Update          | Join this collection       | Leave this collection          |
      | collection | document     | yes        | Goodbye world | Publish         | Join this collection       | Leave this collection          |
      | collection | event        | yes        | Goodbye world | Publish         | Join this collection       | Leave this collection          |
      | collection | news         | yes        | Goodbye world | Publish         | Join this collection       | Leave this collection          |
      | collection | discussion   | no         | Goodbi world  | Update          | Join this collection       | Leave this collection          |
      | collection | document     | no         | Goodbi world  | Update          | Join this collection       | Leave this collection          |
      | collection | event        | no         | Goodbi world  | Update          | Join this collection       | Leave this collection          |
      | collection | news         | no         | Goodbi world  | Update          | Join this collection       | Leave this collection          |
      | solution   | discussion   | yes        | Goodbi world  | Update          | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | document     | yes        | Goodbye world | Publish         | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | news         | yes        | Goodbye world | Publish         | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | event        | yes        | Goodbye world | Publish         | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | discussion   | no         | Goodbi world  | Update          | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | document     | no         | Goodbi world  | Update          | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | news         | no         | Goodbi world  | Update          | Subscribe to this solution | Unsubscribe from this solution |
      | solution   | event        | no         | Goodbi world  | Update          | Subscribe to this solution | Unsubscribe from this solution |
