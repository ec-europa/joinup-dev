@api @javascript @group-c
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
      | Username        | E-mail                   | Roles     |
      | Niles Turner    | niles.turner@example.com |           |
      | Modston Modster | modston@example.org      | moderator |
    And the following collection user memberships:
      | collection           | user         | roles       |
      | Tidy Neutron         | Niles Turner | facilitator |
      | Reborn Eternal Gamma | Niles Turner | facilitator |
    And the following solution user memberships:
      | solution                      | user         | roles       |
      | Opensource neutron generators | Niles Turner | facilitator |
      | Gamma-sensible spectroscopy   | Niles Turner | facilitator |

  Scenario Outline: Moderators can feature and unfeature content site-wide.
    Given <content type> content:
      | title                               | collection   | state     | featured |
      | Ionizing radiation types            | Tidy Neutron | validated | no       |
      | Elementary particles standard model | Tidy Neutron | validated | yes      |

    When I am an anonymous user
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should see the following tiles in the correct order:
      | Opensource neutron generators       |
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

    When I am logged in as "Modston Modster"
    # Wait for contextual links to be generated. There is a session race condition that happens when a contextual link
    # has a CSRF token. The session will store the seed if not yet present, but if a new request is made before the
    # session is persisted, the seed won't be found and regenerated. For this reason, the already generated contextual
    # links with CSRF tokens won't be valid anymore.
    And I wait for AJAX to finish
    And I go to the homepage of the "Tidy Neutron" collection
    Then I should see the contextual link "Feature" in the "Ionizing radiation types" tile
    And I should see the contextual link "Remove from featured" in the "Elementary particles standard model" tile
    But I should not see the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    And I should not see the contextual link "Feature" in the "Elementary particles standard model" tile

    When I click the contextual link "Feature" in the "Ionizing radiation types" tile
    Then I should see the success message "<label> Ionizing radiation types has been set as featured content."
    # Content should be marked as featured only in "global" pages.
    But the "Ionizing radiation types" <content type> tile should not be marked as featured

    When I click "Keep up to date" in the "Header menu" region
    Then the "Ionizing radiation types" tile should be marked as featured

    When I click the contextual link "Remove from featured" in the "Ionizing radiation types" tile
    Then I should see the success message "<label> Ionizing radiation types has been removed from the featured contents."
    And the "Ionizing radiation types" <content type> tile should not be marked as featured

    Examples:
      | content type | label      |
      | event        | Event      |
      | document     | Document   |
      | discussion   | Discussion |
      | news         | News       |

  Scenario Outline: Moderators can feature and unfeature collections and solutions site-wide.
    When I am an anonymous user
    And I am on the homepage
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

    When I am logged in as "Modston Modster"
    And I click "<header link>"
    Then I should see the contextual link "Feature" in the "<unfeatured>" tile
    And the "<unfeatured>" <content type> tile should not be marked as featured
    And I should see the contextual link "Remove from featured" in the "<featured>" tile
    And the "<featured>" tile should be marked as featured
    But I should not see the contextual link "Remove from featured" in the "<unfeatured>" tile
    And I should not see the contextual link "Feature" in the "<featured>" tile

    When I click the contextual link "Feature" in the "<unfeatured>" tile
    Then I should see the success message "<label> <unfeatured> has been set as featured content."
    And the "<featured>" tile should be marked as featured

    And I click the contextual link "Remove from featured" in the "<unfeatured>" tile
    Then I should see the success message "<label> <unfeatured> has been removed from the featured contents."
    And the "<unfeatured>" <content type> tile should not be marked as featured

    # After relogging the contextual links should keep working.
    When I log out
    And I am logged in as "Modston Modster"
    And I click "<header link>"
    And I click the contextual link "Feature" in the "<unfeatured>" tile
    Then I should see the success message "<label> <unfeatured> has been set as featured content."

    Examples:
      | header link | featured                      | unfeatured                  | label      | content type |
      | Collections | Tidy Neutron                  | Reborn Eternal Gamma        | Collection | collection   |
      | Solutions   | Opensource neutron generators | Gamma-sensible spectroscopy | Solution   | solution     |
