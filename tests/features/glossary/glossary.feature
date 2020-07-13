@api @group-a
Feature: As a moderator or group facilitator
  I want to be able to add, edit and delete glossary terms.

  Scenario Outline: Test glossary management.

    Given users:
      | Username | Roles  |
      | <user>   | <role> |
    And the following collection:
      | title | A World of Things |
      | state | validated         |
    And the following collection user membership:
      | collection        | user   | roles     |
      | A World of Things | <user> | <og role> |
    And the following solution:
      | title      | Things To Come    |
      | collection | A World of Things |
      | state      | validated         |

    Given I am logged in as <user>
    When I go to the "A World of Things" collection

    Then I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
    But I should not see the link "Glossary"

    When I click "Add glossary term" in the plus button menu
    Then I should see the heading "Add glossary term"
    And the following fields should be present "Glossary term name, Abbreviation, Summary, Definition"

    When I fill in the following:
      | Glossary term name | XFiles                                                                     |
      | Abbreviation       | ABBR                                                                       |
      | Summary            | Summary of the main glossary term definition                               |
      | Definition         | This is the term definition. It can be a very long, long text content body |
    And I press "Save"
    Then I should see the success message "Glossary term XFiles has been created."
    And I should see "This is the term definition. It can be a very long, long text content body"
    But I should not see "Summary of the main glossary term definition"

    When I click "A World of Things" in the Header region

    Then I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
      | Glossary |
    When I click "Glossary"
    Then I should see the heading "A World of Things glossary"
    And I should see the link "XFiles"
    And I should see "ABBR"
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    # When there's only one entry (i.e. one prefix letter) the glossary
    # navigator won't show.
    And I should not see the glossary navigator

    Given glossary content:
      | title    | abbreviation | summary             | definition                       | collection        |
      | XRatings | XRT          | Summary of XRatings | Long, long body definition field | A World of Things |

    When I go to the "A World of Things" collection
    And I click "Glossary"
    And I should see the link "XFiles"
    And I should see "ABBR"
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    And I should see the link "XRatings"
    And I should see "XRT"
    And I should see "Summary of XRatings"
    But I should not see "Long, long body definition field"
    # Both glossary entries start with the same letter. No navigator is shown.
    And I should not see the glossary navigator

    Given glossary content:
      | title    | abbreviation | summary                 | definition                                  | collection        |
      | Alphabet | ABC          | Summary of Alphabet     | Long, long definition field                 | A World of Things |
      | Colors   | CLR          | Summary of Colors       | Colors definition field                     | A World of Things |
      | Smells   | SML          | Smells Like Teen Spirit | With the lights out, it's less dangerous... | A World of Things |

    When I go to the "A World of Things" collection
    And I click "Glossary"
    Then the page should not be cached
    And I should see the link "Alphabet"
    And I should see "ABC"
    And I should see "Summary of Alphabet"
    But I should not see "Long, long definition field"
    And I should see the link "Colors"
    And I should see "CLR"
    And I should see "Summary of Colors"
    But I should not see "Colors definition field"
    And I should see the link "Smells"
    And I should see "SML"
    And I should see "Smells Like Teen Spirit"
    But I should not see "With the lights out, it's less dangerous..."
    And I should see the link "XFiles"
    And I should see "ABBR"
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    And I should see the link "XRatings"
    And I should see "XRT"
    And I should see "Summary of XRatings"
    But I should not see "Long, long body definition field"
    And I should see the glossary navigator "A C S X"

    When I click "A"
    And I should see the link "Alphabet"
    And I should not see the link "Colors"
    And I should not see the link "Smells"
    And I should not see the link "XFiles"
    And I should not see the link "XRatings"
    And I should see the glossary navigator "A C S X (all)"

    When I go to the "Things To Come" solution
    Then I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
    And I should not see the link "Glossary"


    Given I <link visibility> the contextual link "Edit menu" in the "Left sidebar" region
    And I enable "Glossary" in the navigation menu of the "Things To Come" solution
    And I go to the "Things To Come" solution
    Then I should see the following group menu items in the specified order:
      | text     |
      | Overview |
      | Members  |
      | About    |
      | Glossary |
    And the link "Glossary" points outside group

    When I click "Glossary"
    Then I should see the heading "A World of Things glossary"

    Examples:
      | user | role      | og role     | link visibility |
      | nick | moderator |             | should see      |
      | wade |           | facilitator | should not see  |