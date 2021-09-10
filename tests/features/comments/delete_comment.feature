@api @group-a
Feature: Delete comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collection:
      | title | Semantic web fanatics |
      | logo  | logo.png              |
      | state | validated             |
    And users:
      | Username             | E-mail                        | First name  | Family name |
      | Tim Berners Lee      | tim.berners-lee@example.com   | Tim Berners | Lee         |
      | Vicky visitor        | vicky.visitor@example.com     | Vicky       | visitor     |
      | Do Re Mi Facilitator | doremifacilitator@example.com | Do Re Mi    | Facilitator |
    And news content:
      | title                          | body                              | collection            | state     |
      | RDF Schemas for government use | Home for DCAT, ADMS, and the like | Semantic web fanatics | validated |
    And comments:
      | subject         | field_body       | author          | parent                         |
      | ADMS is awesome | Let's all use it | Tim Berners Lee | RDF Schemas for government use |
    And the following collection user memberships:
      | collection            | user                 | roles       |
      | Semantic web fanatics | Do Re Mi Facilitator | facilitator |

  Scenario: Delete comments
    # As the creator of the comment I can delete the comment.
    Given I am logged in as "Tim Berners Lee"
    When I go to the "RDF Schemas for government use" news
    And I click "Delete" in comment #1
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"

    # As another user I don't have access.
    Given I am logged in as "Vicky visitor"
    When I go to the "RDF Schemas for government use" news
    Then I should not see the link "Delete" in comment #1

    # As the moderator of the comment I can delete the comment.
    Given I am logged in as a user with the "moderator" role
    When I go to the "RDF Schemas for government use" news
    And I click "Delete" in comment #1
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"

    # As collection facilitator of the comment I can delete the comment.
    Given I am logged in as "Do Re Mi Facilitator"
    When I go to the "RDF Schemas for government use" news
    And I click "Delete" in comment #1
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"
    Then I press "Delete"
    Then the following email should have been sent:
      | recipient | Tim Berners Lee                                                                                                                                                  |
      | subject   | Joinup: Your comment has been deleted.                                                                                                                            |
      | body      | Do Re Mi Facilitator deleted your comment in "RDF Schemas for government use".To avoid comment moderation in the future, please read our community guidelines at |
