@api @terms @group-a
Feature: Community API
  In order to manage communities programmatically
  As a backend developer
  I need to be able to use the Community API

  Scenario: Programmatically create a community
    Given the following community:
      | title            | Open Data Initiative     |
      | logo             | logo.png                 |
      | banner           | banner.jpg               |
      | moderation       | no                       |
      | closed           | no                       |
      | content creation | facilitators and authors |
      | topic            | E-health                 |
      | state            | validated                |
    Then I should have 1 community

  Scenario: Programmatically create a community using only the name
    Given the following community:
      | title | EU Interoperability Support Group |
      | state | validated                         |
    Then I should have 1 community

  @uploadFiles:logo.png,banner.jpg
  Scenario: Assign ownership when a community is created through UI.
    Given the following owner:
      | name     | type                  |
      | Prayfish | Private Individual(s) |
    And I am logged in as an "authenticated user"
    When I go to the propose community form
    Then I should see the heading "Propose community"
    When I fill in the following:
      | Title       | Community API example                       |
      | Description | We do not care that much about descriptions. |
      # Contact information data.
      | Name        | BasicCommunityAPI Contact                   |
      | E-mail      | basic.community.api@example.com             |
    When I select "Data gathering, data processing" from "Topic"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Prayfish"
    And I press "Add owner"
    And I check "Moderated"
    And I press "Propose"
    Then I should see the heading "Community API example"
    And I should own the "Community API example" community
    # Cleanup step.
    Then I delete the "Community API example" community
    And I delete the "BasicCommunityAPI Contact" contact information
