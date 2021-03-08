@api @group-a
Feature: Machine translation
  As a citizen of the European Union
  I am likely not to have English as my native language
  So it would be helpful to have a button that can translate the page content

  @javascript
  Scenario: Translation link is only visible on content focused pages
    Given collection:
      | title | Brexit    |
      | state | validated |
    And event content:
      | title                      | collection | state     |
      | Confiscating of sandwiches | Brexit     | validated |
    And news content:
      | title                   | collection | state     |
      | Supply chain disruption | Brexit     | validated |
    And document content:
      | title                           | collection | state     |
      | Increased postal delivery costs | Brexit     | validated |
    And discussion content:
      | title                                    | collection | state     |
      | Restriction on animal-based food imports | Brexit     | validated |
    And custom_page content:
      | title                      | collection | state     |
      | Increased credit card fees | Brexit     | validated |
    And glossary content:
      | title               | abbreviation | summary                 | definition                | collection |
      | Digital portability | DP           | Availability of content | Across streaming services | Brexit     |
    And solution:
      | title      | Rejoin the EU |
      | state      | validated     |
      | collection | Brexit        |

    # Inside a collection sidebar, only custom pages, glossary terms (but not
    # the glossary overview) and the about page should be translatable.
    When I go to the "Brexit" collection
    Then I should not see the "Translate" button
    When I click "Members"
    Then I should not see the "Translate" button
    When I click "Glossary"
    Then I should see the "Translate" button
    When I click "Digital portability"
    Then I should see the "Translate" button
    When I click "About"
    Then I should see the "Translate" button
    When I go to the "Increased credit card fees" custom page
    Then I should see the "Translate" button

    # Community content should be translatable.
    When I go to the "Confiscating of sandwiches" event
    Then I should see the "Translate" button
    When I go to the "Supply chain disruption" news
    Then I should see the "Translate" button
    When I go to the "Increased postal delivery costs" document
    Then I should see the "Translate" button
    When I go to the "Restriction on animal-based food imports" discussion
    Then I should see the "Translate" button

    # Pages that primarily list content should not be translatable.
    When I go to the homepage
    Then I should not see the "Translate" button
    When I visit the search page
    Then I should not see the "Translate" button
    When I click "Collections" in the "Header menu" region
    Then I should not see the "Translate" button
    When I click "Solutions" in the "Header menu" region
    Then I should not see the "Translate" button
    When I click "Keep up to date" in the "Header menu" region
    Then I should not see the "Translate" button

    # Pages that are not content focused should not be translatable.
    When I click "Contact Joinup Support" in the "Footer" region
    Then I should not see the "Translate" button
