@api @group-d
Feature:
  In order to better manage my group members
  As a group owner
  I need to be able to give extra permissions to authors.

  Scenario Outline: Authors can add content regardless of group settings.
    Given collection:
      | title | Author collection |
      | state | validated         |
    And solution:
      | title            | Author solution    |
      | moderation       | <moderation>       |
      | content creation | <content creation> |
      | state            | validated          |
      | collection       | Author collection  |

    When I am logged in as an "author" of the "Author solution" solution
    And I go to the "Author solution" solution
    Then I should see the following links:
      | Add news       |
      | Add discussion |
      | Add document   |
      | Add event      |

    Examples:
      | moderation | content creation         |
      | yes        | registered users         |
      | yes        | facilitators and authors |
      | no         | registered users         |
      | no         | facilitators and authors |