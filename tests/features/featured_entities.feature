@api @email @javascript
Feature: Featuring content site-wide
  As a moderator of Joinup
  I want to feature content in the website
  So that important content has more visibility

  Background:
    Given the following collections:
      | title                | state     | featured |
      | Tidy Neutron         | validated | yes      |
      | Reborn Eternal Gamma | validated | no       |
    And the following solutions:
      | title                         | collection           | state     | featured |
      | Opensource neutron generators | Tidy Neutron         | validated | yes      |
      | Gamma-sensible spectroscopy   | Reborn Eternal Gamma | validated | no       |
    And users:
      | Username     | E-mail                   |
      | Niles Turner | niles.turner@example.com |
    And the following collection user memberships:
      | collection           | user         | roles       |
      | Tidy Neutron         | Niles Turner | facilitator |
      | Reborn Eternal Gamma | Niles Turner | facilitator |
    And the following solution user memberships:
      | solution                      | user         | roles       |
      | Opensource neutron generators | Niles Turner | facilitator |
      | Gamma-sensible spectroscopy   | Niles Turner | facilitator |
    And discussion content:
      | title               | collection   | state     |
      | Protons vs neutrons | Tidy Neutron | validated |

  Scenario Outline: Moderators can feature and unfeature content site-wide.
    Given <content type> content:
      | title                               | collection   | state     | featured |
      | Ionizing radiation types            | Tidy Neutron | validated | no       |
      | Elementary particles standard model | Tidy Neutron | validated | yes      |

    When I am an anonymous user
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should see the following tiles in the correct order:
      | Protons vs neutrons                 |
      | Ionizing radiation types            |
      | Elementary particles standard model |
    And I should not see the contextual link "Feature" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Feature" in the "Elementary particles standard model" tile
    And I should not see the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Remove from featured" in the "Elementary particles standard model" tile

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should not see the contextual link "Feature" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Feature" in the "Elementary particles standard model" tile
    And I should not see the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Remove from featured" in the "Elementary particles standard model" tile

    # Facilitators cannot use the global featured functionality.
    When I am logged in as "Niles Turner"
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should not see the contextual link "Feature" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Feature" in the "Elementary particles standard model" tile
    And I should not see the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Remove from featured" in the "Elementary particles standard model" tile

    When I am logged in as a moderator
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should see the contextual link "Feature" in the "Ionizing radiation types" tile
    And I should see the contextual link "Remove from featured" in the "Elementary particles standard model" tile
    But I should not see the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Feature" in the "Elementary particles standard model" tile

    When I click the contextual link "Feature" in the "Ionizing radiation types" tile
    Then I should see the success message "<label> Ionizing radiation types has been set as featured content."

    When I click the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    Then I should see the success message "<label> Ionizing radiation types has been removed from the feature contents."

    Examples:
      | content type | label      |
      | event        | Event      |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |

  Scenario Outline: Moderators can feature and unfeature collections and solutions site-wide.
    When I am an anonymous user
    And I click "<header link>" in the "Header" region
    Then I should see the following tiles in the correct order:
      | <featured>   |
      | <unfeatured> |
    And I should not see the contextual link "Feature" in the "<featured>" tile
    And I should not see the contextual link "Remove from featured" in the "<unfeatured>" tile

    When I am logged in as an "authenticated user"
    And I click "<header link>"
    Then I should not see the contextual link "Feature" in the "<featured>" tile
    And I should not see the contextual link "Remove from featured" in the "<unfeatured>" tile

    # Facilitators cannot use the global featured functionality.
    When I am logged in as "Niles Turner"
    And I click "<header link>"
    Then I should not see the contextual link "Feature" in the "<featured>" tile
    And I should not see the contextual link "Remove from featured" in the "<unfeatured>" tile

    When I am logged in as a moderator
    And I click "<header link>"
    Then I should see the contextual link "Feature" in the "<unfeatured>" tile
    And I should see the contextual link "Remove from featured" in the "<featured>" tile
    But I should not see the contextual link "Remove from featured" in the "<unfeatured>" tile
    And I should not see the contextual link "Feature" in the "<featured>" tile

    When I click the contextual link "Feature" in the "<unfeatured>" tile
    Then I should see the success message "<label> <unfeatured> has been set as featured content."

    And I click the contextual link "Remove from featured" in the "<unfeatured>" tile
    Then I should see the success message "<label> <unfeatured> has been removed from the feature contents."

    Examples:
      | header link | featured                      | unfeatured                  | label      |
      | Collections | Tidy Neutron                  | Reborn Eternal Gamma        | Collection |
      | Solutions   | Opensource neutron generators | Gamma-sensible spectroscopy | Solution   |