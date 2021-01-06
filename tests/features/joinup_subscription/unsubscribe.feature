@api
Feature: Unsubscribe from collections
  In order to reduce the amount of notifications I receive
  As a user of the website
  I need to be able to easily unsubscribe from collections.

  @wip
  Scenario Outline: Unsubscribe from all groups
    Given user:
      | Username | Eric Cartman             |
      | E-mail   | eric.cartman@example.com |
    Given <group>s:
      | title                | state     |
      | Southpark elementary | validated |
      | Kenny's house        | draft     |
      | Koon's hideout       | proposed  |
    And the following <group> user memberships:
      | <group>              | user         | roles       |
      | Southpark elementary | Eric Cartman | member      |
      | Kenny's house        | Eric Cartman | owner       |
      | Koon's hideout       | Eric Cartman | facilitator |
    And the following <group> content subscriptions:
      | <group>              | user         | subscriptions                     |
      | Southpark elementary | Eric Cartman | discussion, document, event, news |
      | Kenny's house        | Eric Cartman | discussion, document, event, news |
      | Koon's hideout       | Eric Cartman | discussion, document, event, news |

    Given I am logged in as "Eric Cartman"
    When I click "My subscriptions"
    And I click "<button label>" in the "Content" region
    # The button is actually a link but is styled as a button.
    Then I should see the link "Unsubscribe from all"
    And the following <group> content subscriptions should be selected:
      | Southpark elementary | discussion, document, event, news |
      | Kenny's house        | discussion, document, event, news |
      | Koon's hideout       | discussion, document, event, news |

    When I click "Unsubscribe from all"
    Then I should see the heading "Unsubscribe from all <group>s"
    And I should see the following lines of text:
      | Are you sure you want to unsubscribe from all <group>s?            |
      | You will stop receiving news and updates from all <group>s         |
      | In the future you will not be notified for the following <group>s: |
      | Southpark elementary                                               |
      | Kenny's house                                                      |
      | Koon's hideout                                                     |

    When I press "Confirm"
    And I wait for the batch process to finish

    # Checks partially the success messages so it can work like the step that asserts 'lines of text' above.
    Then I should see the following success messages:
      | Success messages                                                       |
      | You will no longer receive notifications for the following 3 <group>s: |
      | Southpark elementary                                                   |
      | Kenny's house                                                          |
      | Koon's hideout                                                         |

    And the following "<group>" content subscriptions should be selected:
      | Southpark elementary |  |
      | Kenny's house        |  |
      | Koon's hideout       |  |
    And I should not see the link "Unsubscribe from all"

    And I should have the following "<group>" content subscriptions:
      | Southpark elementary |  |
      | Kenny's house        |  |
      | Koon's hideout       |  |

    Examples:
      | group      | button label |
      | collection | Collections  |
      | solution   | Solutions    |
