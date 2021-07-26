@api @group-b
Feature:
  In order to have better presentation of my content
  as a user of the website
  I need to be able to see the content listed in the appropriate view mode.

  Scenario: Present custom page solution listings in tiles.
    Given the following owner:
      | name                   | type    |
      | Some fancy named owner | Company |
    And the following contact:
      | name  | Some fancy named contact |
      | email | who.cares@example.com    |
    And the following solutions:
      | title         | description | owner                  | contact information      | state     |
      | Tile solution | Meh...      | Some fancy named owner | Some fancy named contact | validated |
    And the following releases:
      | title        | release number | release notes | is version of | owner                  | contact information      | state     |
      | Tile release | 1              | Meh...        | Tile solution | Some fancy named owner | Some fancy named contact | validated |
    And the following distributions:
      | title       | description | parent       | access url |
      | Tile distro | meh...      | Tile release | test.zip   |
    And the following licences:
      | title        | description |
      | Tile licence | Meh...      |
    And news content:
      | title     | headline  | body   | solution      | state     |
      | Tile news | Tile news | Meh... | Tile solution | validated |
    And event content:
      | title      | short title | body   | agenda        | location   | organisation        | scope         | solution      | state     |
      | Tile event | Tile event  | Meh... | Event agenda. | Some place | European Commission | International | Tile solution | validated |
    And document content:
      | title         | document type | short title | body   | solution      | state     |
      | Tile document | Document      | Meh...      | Meh... | Tile solution | validated |
    And discussion content:
      | title           | body   | solution      | state     |
      | Tile discussion | Meh... | Tile solution | validated |

    When I am logged in as a moderator
    And I go to the homepage of the "Tile solution" solution
    And I click "Add custom page"

    When I fill in the following:
      | Title | Tile custom page |
      | Body  | Meh...           |
    And I press "Add Content listing"
    When I press "Save"

    Then I should see the "Tile release" tile
    And I should see the "Tile distro" tile
    And I should see the "Tile news" tile
    And I should see the "Tile event" tile
    And I should see the "Tile document" tile
    And I should see the "Tile discussion" tile
    But I should not see the "Tile licence" tile

  Scenario: Present custom page community listings in tiles.
    Given the following owner:
      | name                   | type    |
      | Some fancy named owner | Company |
    And the following contact:
      | name  | Some fancy named contact |
      | email | who.cares@example.com    |
    And the following communities:
      | title           | description | owner                  | contact information      | state     |
      | Tile community | Meh...      | Some fancy named owner | Some fancy named contact | validated |
    And news content:
      | title     | headline  | body   | community      | state     |
      | Tile news | Tile news | Meh... | Tile community | validated |
    And event content:
      | title      | short title | body   | agenda        | location   | organisation        | scope         | community      | state     |
      | Tile event | Tile event  | Meh... | Event agenda. | Some place | European Commission | International | Tile community | validated |
    And document content:
      | title         | document type | short title | body   | community      | state     |
      | Tile document | Document      | Meh...      | Meh... | Tile community | validated |
    And discussion content:
      | title           | body   | community      | state     |
      | Tile discussion | Meh... | Tile community | validated |

    When I am logged in as a moderator
    And I go to the homepage of the "Tile community" collection
    And I click "Add custom page"

    When I fill in the following:
      | Title | Tile custom page |
      | Body  | Meh...           |
    And I press "Add Content listing"
    When I press "Save"

    And I should see the "Tile news" tile
    And I should see the "Tile event" tile
    And I should see the "Tile document" tile
    And I should see the "Tile discussion" tile
