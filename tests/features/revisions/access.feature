@api @group-b
Feature:
  As a user of the website
  In order to be able to manage the history of my content
  I need to have access to the revisions when available.

  Scenario Outline: Test access to revisions and possible proposed state on return.
    Given <group type>:
      | title       | Hellow world                              |
      | description | Because I do not know how to spell Hello. |
      | moderation  | <moderation>                              |
      | state       | validated                                 |
    And users:
      | Username      | E-mail                   |
      | Clumsy Bounce | clumsy.ounce@example.com |
    And <content type> content:
      | title        | description                            | state     | author        | <group type> |
      | Goodbi world | Because I cannot spell Goodbye either. | validated | Clumsy Bounce | Hellow world |

    When I am logged in as a user with the authenticated role
    And I visit the "Goodbi world" <content type>
    Then I should not see the "Entity actions" region

    When I am logged in as a user with the moderator role
    And I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions" in the "Entity actions" region

    When I am logged in as a facilitator of the "Hellow world" <group type>
    And I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions" in the "Entity actions" region

    When I am logged in as "Clumsy Bounce"
    And I visit the "Goodbi world" <content type>
    Then I should not see the link "Revisions" in the "Entity actions" region

    Given <content type> revisions:
      | current title | title         |
      | Goodbi world  | Goodbye world |

    When I am logged in as a user with the authenticated role
    And I visit the "Goodbye world" <content type>
    Then I should not see the "Entity actions" region

    When I am logged in as a user with the moderator role
    And I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region

    When I am logged in as a facilitator of the "Hellow world" <group type>
    And I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region

    When I am logged in as "Clumsy Bounce"
    And I visit the "Goodbye world" <content type>
    Then I should see the link "Revisions" in the "Entity actions" region

    When I click "Revisions" in the "Entity actions" region
    Then I should see the button "Compare"
    And I should see the link "Revert"
    And I should see the link "Delete"

    Given I click "Revert"
    And I press "Revert"

    # If the parent group is moderated, the "Goodbye title" title will be shown here because the new version will
    # proposed, so non published. The same applies for the rest of the steps.
    Then I should see the heading "Revisions for <final title>"
    Given I go to the "<final title>" <content type>
    Then I should see the heading "<final title>"

    Examples:
      | group type | content type | moderation | final title   |
      | collection | discussion   | yes        | Goodbye world |
      | collection | document     | no         | Goodbi world  |
      | collection | event        | yes        | Goodbye world |
      | collection | news         | no         | Goodbi world  |
      | solution   | document     | yes        | Goodbye world |
      | solution   | discussion   | no         | Goodbi world  |
      | solution   | news         | yes        | Goodbye world |
      | solution   | event        | no         | Goodbi world  |
