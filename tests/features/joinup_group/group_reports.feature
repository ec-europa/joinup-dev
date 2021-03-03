@api @group-a
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
    When I am not logged in
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Normal authenticated users can never access the group reports.
    When I am logged in as an "authenticated user"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Moderators can access all group reports.
    When I am logged in as a "moderator"
    And I go to the "Muscle tissue formation" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    And I go to the "Cultured meat technology" collection
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
    When I am logged in as "keiko"
    And I go to the "Muscle tissue formation" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Muscle tissue formation".
    When I am logged in as "juro"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Cultured meat technology".
    When I am logged in as "daichi"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Cultured meat technology".
    When I am logged in as "yumiko"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Aligning myotubes".
    When I am logged in as "kyouko"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"
    When I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Member of "Aligning myotubes".
    When I am logged in as "tsubasa"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region

    # Facilitator of "Increasing global meat demand".
    When I am logged in as "hoshiko"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should see the link "Reports" in the "Entity actions" region
    When I click "Reports" in the "Entity actions" region
    Then I should see the heading "Reports"

   # Member of "Increasing global meat demand".
    When I am logged in as "takehiko"
    And I go to the "Muscle tissue formation" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Cultured meat technology" collection
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Aligning myotubes" solution
    Then I should not see the link "Reports" in the "Entity actions" region
    And I go to the "Increasing global meat demand" solution
    Then I should not see the link "Reports" in the "Entity actions" region
