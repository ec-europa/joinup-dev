@api @group-c
Feature:
  In order to understand how my group is being used
  As a facilitator
  I want to have access to a page listing group reports

  Background:
    Given users:
      | Username | Roles     | First name | Family name |
      | keiko    |           | Keiko      | Kuroki      |
      | juro     |           | Juro       | Yamasaki    |
      | daichi   |           | Daichi     | Ogawa       |
      | yumiko   |           | Yumiko     | Ueda        |
      | kyouko   |           | Kyouko     | Kato        |
      | tsubasa  |           | Tsubasa    | Akiyama     |
      | hoshiko  |           | Hoshiko    | Watanabe    |
      | takehiko |           | Takehiko   | Moto        |
    And collections:
      | title                    | state     |
      | Muscle tissue formation  | validated |
      | Cultured meat technology | validated |
    And solutions:
      | title                         | state     | collection               |
      | Aligning myotubes             | validated | Muscle tissue formation  |
      | Increasing global meat demand | validated | Cultured meat technology |
    And collection user memberships:
      | collection                  | user   | roles       |
      | Muscle tissue formation     | keiko  | facilitator |
      | Muscle tissue formation     | juro   |             |
      | Cultured meat technology    | daichi | facilitator |
      | Cultured meat technology    | yumiko |             |
    And solution user memberships:
      | solution                      | user     | roles       |
      | Aligning myotubes             | kyouko   | facilitator |
      | Aligning myotubes             | tsubasa  |             |
      | Increasing global meat demand | hoshiko  | facilitator |
      | Increasing global meat demand | takehiko |             |

  Scenario: Access the group reports page
    # Anonymous users can never access the group reports.
    Given I am not logged in
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Normal authenticated users can never access the group reports.
    Given I am logged in as an "authenticated user"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Moderators can access all group reports.
    Given I am logged in as a "moderator"
    When I go to the "Muscle tissue formation" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Cultured meat technology" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Aligning myotubes" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Increasing global meat demand" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"

    # Facilitator of "Muscle tissue formation".
    Given I am logged in as "keiko"
    When I go to the "Muscle tissue formation" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Muscle tissue formation".
    Given I am logged in as "juro"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Cultured meat technology".
    Given I am logged in as "daichi"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Cultured meat technology".
    Given I am logged in as "yumiko"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Aligning myotubes".
    Given I am logged in as "kyouko"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Aligning myotubes".
    Given I am logged in as "tsubasa"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Increasing global meat demand".
    Given I am logged in as "hoshiko"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"

   # Member of "Increasing global meat demand".
    Given I am logged in as "takehiko"
    When I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Let's do a quick sanity check of the reports page to see if the page
    # renders, and that the group header is visible.
    Given I am logged in as a "moderator"
    When I go to the "Muscle tissue formation" collection
    And I click "Reports"
    Then I should see the heading "Muscle tissue formation" in the "Header" region
    And I should see the heading "Reports" in the "Page title"
    When I go to the "Aligning myotubes" solution
    And I click "Reports"
    Then I should see the heading "Aligning myotubes" in the "Header" region
    And I should see the heading "Reports" in the "Page title"
