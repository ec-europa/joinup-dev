@api @group-e
Feature: Type something to filter the listing the member list
  In order to quickly find a particular member
  As a moderator
  I need to be able to filter the member list

  Background:
    Given users:
      | Username   | First name | Family name | Roles     | E-mail                 |
      | séamusline | Séamus     | Kingsbrooke | moderator | seamusline@example.com |
      | emeritous  | King       | Seabrooke   |           | emeritous@example.com  |
      | user049230 | King       | Emerson     |           | user049230@example.com |
      | kingseamus | Seamus     | Emerson     |           | kingseamus@example.com |
      | brookebeau | Brooke     | Kingsley    |           | brookebeau@example.com |
      | iambroke   | Nell       | Gibb        |           | iambroke@example.com   |
      | queenson   | Queen      | Emerson     |           | queenson@example.com   |
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
    And I am on the members page of "Coffee makers"
    Then the following fields should be present "Type something to filter the list, Roles"

    Examples:
      | user       |
      | séamusline |
      | emeritous  |
      | user049230 |
      | kingseamus |
      | queenson   |

  Scenario: Moderators should be able to filter users in the table in the collection members page.
    Given I am logged in as "séamusline"
    And I am on the members page of "Coffee makers"
    When I fill in "Type something to filter the list" with "bro"
    And I press "Apply"

    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
    # Clicking "Name" will sort the table by descending name order.
    When I click "Name"
    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    # Clicking "Member since" will sort the table by ascending created order.
    When I click "Member since"
    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    # Clicking "Member since" again will sort the table by descending created order.
    When I click "Member since"
    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |

    # Clicking "State" will sort the table by ascending state order.
    When I click "State"
    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    # Clicking "State" again will sort the table by descending state order.
    When I click "State"
    Then the "member administration" table should contain the following columns:
      | Name            | Member since            | State   | Roles                                    |
      | King Seabrooke  | Mon, 01/01/2018 - 00:00 | active  | Collection owner, Collection facilitator |
      | Brooke Kingsley | Thu, 01/03/2018 - 00:00 | active  |                                          |

    When I fill in "Type something to filter the list" with "King"
    And I press "Apply"
    Then I should see the link "King Seabrooke"
    And I should see the link "King Emerson"
    And I should see the link "Brooke Kingsley"

    When I clear the field "Type something to filter the list"
    And I press "Apply"
    And I select "Owner (1)" from "Roles"
    And I press "Apply"
    Then I should see the link "King Seabrooke"
    But I should not see the link "King Emerson"
    And I should not see the link "Brooke Kingsley"
    And I should not see the link "Nell Gibb"

    When I select "Facilitator (2)" from "Roles"
    And I press "Apply"
    Then I should see the link "King Emerson"
    And I should see the link "King Seabrooke"
    And I should not see the link "Brooke Kingsley"
    And I should not see the link "Nell Gibb"
    # "eme" also matches "King Seabrooke" due to the username "emeritous"
    And I fill in "Type something to filter the list" with "eme"
    And I press "Apply"
    Then I should see the link "King Emerson"
    But I should not see the link "King Seabrooke"
    And the option with text "Facilitator (1)" from select "Roles" is selected

    # Ensure that filtering is based in all words.
    When I fill in "Type something to filter the list" with "King Emerson"
    And I press "Apply"
    Then I should see the link "King Emerson"
    But I should not see the link "King Seabrooke"
    And the option with text "Facilitator (1)" from select "Roles" is selected
