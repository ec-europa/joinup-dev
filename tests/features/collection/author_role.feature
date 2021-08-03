@api @group-b
Feature:
  In order to better manage my group members
  As a group owner
  I need to be able to give extra permissions to authors.

  Scenario Outline: Authors can add content regardless of group settings.
    Given community:
      | title            | Author community  |
      | moderation       | <moderation>       |
      | content creation | <content creation> |
      | state            | validated          |

    When I am logged in as an "author" of the "Author community" community
    And I go to the "Author community" community
    Then I should see the following links:
      | Add solution   |
      | Add news       |
      | Add discussion |
      | Add document   |
      | Add event      |

    Examples:
      | moderation | content creation         |
      | yes        | registered users         |
      | yes        | members                  |
      | yes        | facilitators and authors |
      | no         | registered users         |
      | no         | members                  |
      | no         | facilitators and authors |
