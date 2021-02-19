@api
Feature: Unsubscribe from collections
  In order to reduce the amount of notifications I receive
  As a user of the website
  I need to be able to easily unsubscribe from collections.

  Scenario: Unsubscribe from all groups
    Given user:
      | Username | Eric Cartman             |
      | E-mail   | eric.cartman@example.com |
    And collections:
      | title                | state     |
      | Southpark elementary | validated |
      | Kenny's house        | draft     |
      | Koon's hideout       | proposed  |
    And the following collection user memberships:
      | collection           | user         | roles       |
      | Southpark elementary | Eric Cartman | member      |
      | Kenny's house        | Eric Cartman | owner       |
      | Koon's hideout       | Eric Cartman | facilitator |
    And the following collection content subscriptions:
      | collection           | user         | subscriptions                     |
      | Southpark elementary | Eric Cartman | discussion, document, event, news |
      | Kenny's house        | Eric Cartman | discussion, document, event, news |
      | Koon's hideout       | Eric Cartman | discussion, document, event, news |
    And solutions:
      | title              | state     |
      | Poor man's Shelter | validated |
      | Anna's house       | draft     |
      | Greg's foxhole     | proposed  |
    And the following solution user memberships:
      | solution           | user         | roles       |
      | Poor man's Shelter | Eric Cartman | member      |
      | Anna's house       | Eric Cartman | owner       |
      | Greg's foxhole     | Eric Cartman | facilitator |
    And the following solution content subscriptions:
      | solution           | user         | subscriptions                     |
      | Poor man's Shelter | Eric Cartman | discussion, document, event, news |
      | Anna's house       | Eric Cartman | discussion, document, event, news |
      | Greg's foxhole     | Eric Cartman | discussion, document, event, news |

    Given I am logged in as "Eric Cartman"
    When I click "My subscriptions"

    Then I should see the link "Unsubscribe from all"
    And the following content subscriptions should be selected:
      | Southpark elementary | discussion, document, event, news |
      | Kenny's house        | discussion, document, event, news |
      | Koon's hideout       | discussion, document, event, news |
      | Poor man's Shelter   | discussion, document, event, news |
      | Anna's house         | discussion, document, event, news |
      | Greg's foxhole       | discussion, document, event, news |

    When I click "Unsubscribe from all"
    Then I should see the heading "Unsubscribe from all?"

    And I should see the following lines of text:
      | Are you sure you want to unsubscribe from all collections and/or solutions? You will stop receiving news and updates, including the pending memberships, from the following: |
      | Collections                                                                                                                                                                  |
      | Southpark elementary                                                                                                                                                         |
      | Kenny's house                                                                                                                                                                |
      | Koon's hideout                                                                                                                                                               |
      | Solutions                                                                                                                                                                    |
      | Poor man's Shelter                                                                                                                                                           |
      | Anna's house                                                                                                                                                                 |
      | Greg's foxhole                                                                                                                                                               |

    When I click "Cancel"
    Then I should see the heading "My subscriptions"

    When I click "Unsubscribe from all"
    And I press "Confirm"
    And I wait for the batch process to finish

    # Checks partially the success messages so it can work like the step that asserts 'lines of text' above.
    Then I should see the following success messages:
      | success messages                                            |
      | You will no longer receive notifications for the following: |
      | Collections                                                 |
      | Kenny's house                                               |
      | Koon's hideout                                              |
      | Southpark elementary                                        |
      | Solutions                                                   |
      | Poor man's Shelter                                          |
      | Anna's house                                                |
      | Greg's foxhole                                              |

    And the following content subscriptions should be selected:
      | Kenny's house        |  |
      | Koon's hideout       |  |
      | Southpark elementary |  |
      | Poor man's Shelter   |  |
      | Anna's house         |  |
      | Greg's foxhole       |  |

    And I should not see the link "Unsubscribe from all"

    And I should have the following content subscriptions:
      | Southpark elementary |  |
      | Kenny's house        |  |
      | Koon's hideout       |  |
      | Poor man's Shelter   |  |
      | Anna's house         |  |
      | Greg's foxhole       |  |
