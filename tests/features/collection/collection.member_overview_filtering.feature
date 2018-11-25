@api
Feature: Filtering the member list
  In order to quickly find a particular member
  As a moderator
  I need to be able to filter the member list

  Background:
    Given users:
      | Username   | First name | Family name | Roles     |
      | séamusline | Séamus     | Kingsbrooke | moderator |
      | emeritous  | King       | Seabrooke   |           |
      | user049230 | King       | Emerson     |           |
      | kingseamus | Seamus     | Emerson     |           |
      | brookebeau | Brooke     | Kingsley    |           |
      | iambroke   | Nell       | Gibb        |           |
      | queenson   | Queen      | Emerson     |           |
    And the following collection:
      | title         | Coffee makers                  |
      | description   | Coffee is needed for survival. |
      | state         | validated                      |
      | creation date | 01-01-2018                     |
    And the following collection user memberships:
      | collection    | user       | roles       | created          | state   |
      | Coffee makers | emeritous  | owner       | 01-01-2018 00:00 | active  |
      | Coffee makers | user049230 | facilitator | 02-01-2018 00:00 | active  |
      | Coffee makers | kingseamus |             | 05-07-2018 00:00 | active  |
      | Coffee makers | brookebeau |             | 01-03-2018 00:00 | active  |
      | Coffee makers | iambroke   |             | 01-02-2018 00:00 | blocked |

  Scenario Outline: All users are allowed to filter the users by a combined search field and the role.
    Given I am logged in as "<user>"
    When I go to the homepage of the "Coffee makers" collection
    And I click "Members" in the "Left sidebar"
    Then the following fields should be present "Filter, Roles"

    Examples:
      | user       |
      | séamusline |
      | emeritous  |
      | user049230 |
      | kingseamus |
      | queenson   |

  Scenario: Moderators should be able to filter users in the table in the collection members page.
    When I am logged in as "séamusline"
    And I go to the homepage of the "Coffee makers" collection
    And I click "Members" in the "Left sidebar"
    And I fill in "Filter" with "bro"
    And I press "Apply"

    Then the "member administration" table should be:
      # The first column is empty as it contains the bulk operations checkbox.
      # @todo: Fix this after ISAICP-4836 is implemented.
      |  | Name            | Member since            | State   | Roles                                    |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |
    # Clicking "Name" will sort the table by descending name order.
    When I click "Name"
    Then the "member administration" table should be:
      |  | Name            | Member since            | State   | Roles                                    |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    # Clicking "Member since" will sort the table by ascending created order.
    When I click "Member since"
    Then the "member administration" table should be:
      |  | Name            | Member since            | State   | Roles                                    |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    # Clicking "Member since" again will sort the table by descending created order.
    When I click "Member since"
    Then the "member administration" table should be:
      |  | Name            | Member since            | State   | Roles                                    |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |

    # Clicking "State" will sort the table by ascending state order.
    When I click "State"
    Then the "member administration" table should be:
      |  | Name            | Member since            | State   | Roles                                    |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |

    # Clicking "State" again will sort the table by descending state order.
    When I click "State"
    Then the "member administration" table should be:
      |  | Name            | Member since            | State   | Roles                                    |
      |  | Nell Gibb       | Thu, 01/02/2018 - 00:00 | blocked |                                          |
      |  | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      |  | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    When I fill in "Filter" with "King"
    And I press "Apply"
    Then I should see the link "King Seabrooke"
    And I should see the link "King Emerson"
    And I should see the link "Brooke Kingsley"
    # "King" also matches "Seamus Emerson" due to the username "kingseamus".
    # Search is case insensitive.
    And I should see the link "Seamus Emerson"

    When I clear the field "Filter"
    And I press "Apply"
    And I select "Owner (1)" from "Roles"
    And I press "Apply"
    Then I should see the link "King Seabrooke"
    But I should not see the link "King Emerson"
    And I should not see the link "Seamus Emerson"
    And I should not see the link "Brooke Kingsley"
    And I should not see the link "Nell Gibb"

    When I select "Facilitator (2)" from "Roles"
    And I press "Apply"
    Then I should see the link "King Emerson"
    And I should see the link "King Seabrooke"
    But I should not see the link "Seamus Emerson"
    And I should not see the link "Brooke Kingsley"
    And I should not see the link "Nell Gibb"
    # "eme" also matches "King Seabrooke" due to the username "emeritous"
    And I fill in "Filter" with "eme"
    And I press "Apply"
    Then I should see the link "King Emerson"
    And I should see the link "King Seabrooke"
    And the option with text "Facilitator (2)" from select "Roles" is selected
