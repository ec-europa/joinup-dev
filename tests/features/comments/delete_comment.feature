@api @email
Feature: Delete comments
  As a visitor of the website I can leave a comment on community content.

  Background:
    Given the following collection:
      | title | Semantic web fanatics |
      | logo  | logo.png              |
      | state | validated             |
    And users:
      | Username             | E-mail                        |
      | Tim Berners Lee      | tim.berners-lee@example.com   |
      | Vicky visitor        | vicky.visitor@example.com     |
      | Do Re Mi Facilitator | doremifacilitator@example.com |
    And news content:
      | title                          | body                              | collection            | status    |
      | RDF Schemas for government use | Home for DCAT, ADMS, and the like | Semantic web fanatics | published |
    And comments:
      | subject         | field_body       | author          | parent                         |
      | ADMS is awesome | Let's all use it | Tim Berners Lee | RDF Schemas for government use |
    And the following collection user memberships:
      | collection            | user                 | roles       |
      | Semantic web fanatics | Do Re Mi Facilitator | facilitator |

  Scenario: Delete comments
    # As the creator of the comment I can delete the comment.
    Given I am logged in as "Tim Berners Lee"
    When I go to the "RDF Schemas for government use" news page
    When I click the contextual link "Delete comment" in the "Comment" region
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"

    # As another user I don't have access.
    Given I am logged in as "Vicky visitor"
    When I go to the "RDF Schemas for government use" news page
    Then I should not see the contextual link "Delete comment" in the "Comment" region

    # As the moderator of the comment I can delete the comment.
    Given I am logged in as a user with the "moderator" role
    When I go to the "RDF Schemas for government use" news page
    When I click the contextual link "Delete comment" in the "Comment" region
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"

    # As collection facilitator of the comment I can delete the comment.
    Given I am logged in as "Do Re Mi Facilitator"
    When I go to the "RDF Schemas for government use" news page
    When I click the contextual link "Delete comment" in the "Comment" region
    Then I should see "Are you sure you want to delete the comment ADMS is awesome?"
    Then I press "Delete"
    Then the following email should have been sent:
      | template  | Message to the comment author when his comment gets deleted |
      | recipient | Tim Berners Lee                                             |
      | subject   | You comment on Joinup has been removed.                     |
      | body      | Your comment                                                |
