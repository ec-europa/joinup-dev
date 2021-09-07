@api @group-a
Feature: As a moderator or group facilitator I want to be able to add, edit and
  delete glossary terms. As a user I want to be able to see glossary terms as
  links to their definition page.

  Scenario Outline: Manage a glossary.
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
    And the following fields should be present "Glossary term name, Summary, Definition, Synonym"

    When I fill in the following:
      | Glossary term name | XFiles                                                                     |
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
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    # When there's only one entry (i.e. one prefix letter) the glossary
    # navigator won't show.
    And I should not see the glossary navigator

    Given glossary content:
      | title    | synonyms                | summary             | definition                       | collection        |
      | XRatings | XRT,X.R.T.,extraratings | Summary of XRatings | Long, long body definition field | A World of Things |

    When I go to the "A World of Things" collection
    And I click "Glossary"
    And I should see the link "XFiles"
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    And I should see the link "XRatings"
    And I should see the link "XRT"
    And I should see the link "X.R.T."
    And I should see the link "extraratings"
    And I should see "Summary of XRatings"
    But I should not see "Long, long body definition field"
    # Both glossary entries start with the same letter. No navigator is shown.
    And I should not see the glossary navigator

    Given glossary content:
      | title    | synonyms              | summary                 | definition                                  | collection        |
      | Alphabet | ABC,alphabeta,alfabet | Summary of Alphabet     | Long, long definition field                 | A World of Things |
      | Colors   | CLR                   | Summary of Colors       | Colors definition field                     | A World of Things |
      | Smells   | SML                   | Smells Like Teen Spirit | With the lights out, it's less dangerous... | A World of Things |

    When I go to the "A World of Things" collection
    And I click "Glossary"
    Then the page should not be cached
    And I should see the link "Alphabet"
    And I should see the link "ABC"
    And I should see the link "alphabeta"
    And I should see the link "alfabet"
    And I should see "Summary of Alphabet"
    But I should not see "Long, long definition field"
    And I should see the link "Colors"
    And I should see the link "CLR"
    And I should see "Summary of Colors"
    But I should not see "Colors definition field"
    And I should see the link "Smells"
    And I should see the link "SML"
    And I should see "Smells Like Teen Spirit"
    But I should not see "With the lights out, it's less dangerous..."
    And I should see the link "XFiles"
    And I should see "Summary of the main glossary term definition"
    But I should not see "This is the term definition. It can be a very long, long text content body"
    And I should see the link "XRatings"
    And I should see the link "XRT"
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

    # Test multi-synonyms display.
    When I click "Alphabet"
    And I should see "ABC, alphabeta, alfabet"

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

  Scenario: Glossary terms should be shown as links in collection content
    Given collection:
      | title       | Collection With Glossary                                                                             |
      | state       | validated                                                                                            |
      | description | Colors of Paradise. Abbreviated as CLR. <a href="../../contact"><strong>Colors of Dream</strong></a> |
      | abstract    | The Alphabet is back and it's s/ashy.                                                                |
    And solution:
      | title       | Under The Bridge         |
      | description | No Colors                |
      | state       | validated                |
      | collection  | Collection With Glossary |
    And release:
      | title          | Summer of 69          |
      | release number | 6.22                  |
      | release notes  | Everything was Colors |
      | is version of  | Under The Bridge      |
      | state          | validated             |
    And distributions:
      | title   | description          | parent           | access url         |
      | Distro1 | Alphabet & CLR       | Under The Bridge | http://example.com |
      | Distro2 | Colors & ABC & colrs | Summer of 69     | http://example.com |
    And custom_page content:
      | title    | body              | solution         |
      | Schedule | Colors everywhere | Under The Bridge |
    And discussion content:
      | title        | content                   | collection               | state     |
      | The Big Talk | The Alphabet. Call it ABC | Collection With Glossary | validated |
    And document content:
      | title             | body             | collection               | state     |
      | Authentic Papyrus | Alphabet is back | Collection With Glossary | validated |
    And event content:
      | title      | body                              | solution         | state     |
      | Soho Night | Any cOLOrs You Like. Maybe colrs? | Under The Bridge | validated |
    And news content:
      | title         | body                                       | solution         | state     |
      | Won at Bingo! | aBC is for ALPHABET what CLR is for Colors | Under The Bridge | validated |
    And glossary content:
      | title    | synonyms  | summary                  | definition                  | collection               |
      | Alphabet | ABC       | Summary of Alphabet      | Long, long definition field | Collection With Glossary |
      | Colors   | CLR,colrs | Summary of Colors        | Colors definition field     | Collection With Glossary |
      | S/ashy   | /S\|ASH   | S/ashes Like an Ion Beam | Cutting things in ha/f      | Collection With Glossary |

    When I go to the "Collection With Glossary" collection
    When I click "Overview"
    And I click "Alphabet"
    Then I see the heading "Alphabet"

    When I click "About"
    Then I should see the link "Alphabet"
    When I click "Colors"
    Then I see the heading "Colors"

    When I move backward one page
    And I click "CLR"
    Then I see the heading "Colors"

    When I move backward one page
    And I click "s/ashy"
    Then I see the heading "S/ashy"

    # A glossary term inside a link text remains untouched.
    When I move backward one page
    And I click "Colors of Dream"
    Then I should see the heading "Contact"

    When I go to the "Under The Bridge" solution
    Then I should see the link "Colors"

    When I go to the "Summer of 69" release
    Then I should see the link "Colors"

    When I go to the "Distro1" distribution
    Then I should see the link "Alphabet"
    And I should see the link "CLR"

    When I go to the "Distro2" distribution
    Then I should see the link "Colors"
    And I should see the link "ABC"
    And I should see the link "colrs"

    When I go to the "Schedule" custom page
    Then I should see the link "Colors"

    When I go to the "The Big Talk" discussion
    Then I should see the link "Alphabet"
    And I should see the link "ABC"

    When I go to the "Authentic Papyrus" document
    Then I should see the link "Alphabet"

    When I go to the "Soho Night" event
    # Test that the replacement is case insensitive.
    Then I should see the link "cOLOrs"
    Then I should see the link "colrs"

    When I go to the "Won at Bingo!" news
    Then I should see the link "Colors"
    And I should see the link "CLR"
    # Test that the replacement is case insensitive.
    And I should see the link "ALPHABET"
    # Test that the synonym replacement is case insensitive.
    And I should see the link "aBC"

  Scenario: Limiting replacements to the first occurrence.
    Given users:
      | Username | Roles     |
      | regular  |           |
      | fac      |           |
      | mod      | moderator |
      | fac1     |           |
    And the following collections:
      | title                    | description                                                                                            | state     |
      | Collection With Glossary | The Battle of Evermore of the batTle and the BATTLE. Call it everMore or EVERmore. Also, battle again. | validated |
      | The Other Collection     | desc                                                                                                   | validated |
    And the following collection user membership:
      | collection               | user | roles       |
      | Collection With Glossary | fac  | facilitator |
      | The Other Collection     | fac1 | facilitator |
    And glossary content:
      | title        | synonyms | definition                                | collection               |
      | battle       | evermore | def                                       | Collection With Glossary |
      | battle again |          | Not to be confused with a battle of sheep | Collection With Glossary |

    Given I am an anonymous user
    When I go to the "Collection With Glossary" collection
    Then I should not see the link "Settings" in the "Entity actions" region
    When I click "Glossary"
    Then I should not see the link "Glossary settings"

    Given I am logged in as regular
    When I go to the "Collection With Glossary" collection
    Then I should not see the link "Settings" in the "Entity actions" region
    When I click "Glossary"
    Then I should not see the link "Glossary settings"

    Given I am logged in as fac1
    When I go to the "Collection With Glossary" collection
    Then I should not see the link "Settings" in the "Entity actions" region
    When I click "Glossary"
    Then I should not see the link "Glossary settings"

    Given I am logged in as mod
    When I go to the "Collection With Glossary" collection
    Then I should see the link "Settings" in the "Entity actions" region
    When I click "Glossary"
    Then I should see the link "Glossary settings"

    Given I am logged in as fac
    When I go to the "Collection With Glossary" collection
    And I click "Settings" in the "Entity actions" region
    Then I should see the heading "Collection With Glossary collection settings"
    And the "Highlight only the first term appearance" checkbox should be checked

    When I go to the "Collection With Glossary" collection

    # Only the first occurrence should be highlighted.
    And I should see the link "Battle"
    And I should see the link "Evermore"
    And I should see the link "battle again"
    And the response should contain "/collection/collection-glossary/glossary/term/battle\" class=\"glossary-term\" title=\"def\">Battle</a>"
    And the response should contain "/collection/collection-glossary/glossary/term/battle-again\" class=\"glossary-term\" title=\"Not to be confused with a battle of sheep\">battle again</a>"
    But I should not see the link "batTle"
    And I should not see the link "BATTLE"
    And I should not see the link "everMore"
    And I should not see the link "EVERmore"

    When I click "Settings" in the "Entity actions" region
    And I uncheck the box "Highlight only the first term appearance"
    And I press "Save"

    # All terms should be highlighted.
    Then I should see the link "Battle"
    And I should see the link "batTle"
    And I should see the link "BATTLE"
    And I should see the link "Evermore"
    And I should see the link "everMore"
    And I should see the link "EVERmore"
    And I should see the link "battle again"

    When I click "Glossary"
    Then I should see the link "Glossary settings"
    When I click "Glossary settings"
    Then I should see the heading "Collection With Glossary collection settings"

  Scenario: Test glossary term name and synonyms collision.
    Given users:
      | Username |
      | ben      |
    And the following collections:
      | title             | state     |
      | A World of Things | validated |
      | Other collection  | validated |

    And the following collection user membership:
      | collection        | user | roles       |
      | A World of Things | ben  | facilitator |
      | Other collection  | ben  | facilitator |

    Given I am logged in as ben
    When I go to the "A World of Things" collection
    And I click "Add glossary term" in the plus button menu
    And I fill in the following:
      | Glossary term name | XFiles     |
      | Definition         | Definition |
    And I press "Save"
    Then I should see the success message "Glossary term XFiles has been created."
    And I click "Add glossary term" in the plus button menu
    And I fill in the following:
      | Glossary term name | XFiles           |
      | Definition         | Other definition |
    When I press "Save"
    Then I should see the error message "The Glossary term glossary term name value (XFiles) is already taken by XFiles."

    # It should be possible to have the same term name in different collections.
    When I go to the "Other collection" collection
    And I click "Add glossary term" in the plus button menu
    And I fill in the following:
      | Glossary term name | XFiles         |
      | Definition         | Same term name |
    And I press "Save"
    Then I should see the success message "Glossary term XFiles has been created."

    # Test glossary term name and synonyms overlapping.
    And glossary content:
      | title     | synonyms                | definition                  | collection        |
      | Alphabet  | ABC, XYZ, whatever      | Long, long definition field | A World of Things |
      | Colors    | CLR,colrs               | Colors definition field     | A World of Things |
      | XRatings  | XRT,X.R.T.,extraratings | definition                  | Other collection  |
      | Duplicate | dupe,DuPe               | duplicate sysnonym          | A World of Things |

    # Hard to fill a multi-value field, so we'll pre-create it with duplicates.
    When I go to the edit form of the "Duplicate" glossary
    And I press "Save"
    Then I should see the error message "The 'DuPe' synonym is duplicated. Keep only one entry."

    When I go to the "A World of Things" collection
    And I click "Add glossary term" in the plus button menu
    And I fill in the following:
      # Test also if the match is case insensitive.
      | Glossary term name | Some |
      | Synonym            | soMe |
      | Definition         | def  |
    And I press "Save"
    Then I should see the error message "A synonym cannot be the same as the glossary term name (Some)."

    And I fill in the following:
      # Test also if the match is case insensitive.
      | Glossary term name | xyz      |
      | Synonym            | alPHABET |
      | Definition         | def      |
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                                                                                               |
      | This glossary term (xyz) name is already used as synonym of Alphabet. You should remove that synonym before using this name. |
      | Some synonyms are already used in other glossary terms either as term name or as term synonyms: alPHABET in Alphabet         |

    And I fill in the following:
      # Test also if the match is case insensitive.
      | Glossary term name | xyz      |
      | Synonym            | whatEVER |
      | Definition         | def      |
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                                                                                               |
      | This glossary term (xyz) name is already used as synonym of Alphabet. You should remove that synonym before using this name. |
      | Some synonyms are already used in other glossary terms either as term name or as term synonyms: whatEVER in Alphabet.        |

    When I fill in "Synonym" with "colrs"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                                                                                               |
      | This glossary term (xyz) name is already used as synonym of Alphabet. You should remove that synonym before using this name. |
      | Some synonyms are already used in other glossary terms either as term name or as term synonyms: colrs in Colors.             |

    # But is allowed to overlap if the other term is in a different collection.
    When I fill in "Glossary term name" with "XRatings"
    And I fill in "Synonym" with "extraratings"
    And I press "Save"
    Then I should see the success message "Glossary term XRatings has been created."

