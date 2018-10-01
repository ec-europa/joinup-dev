@api
Feature: Proof of Concept for private messaging.

  Scenario: Test private messages exchange.

    Given users:
      | Username |
      | glenn    |
      | ian      |
      | david    |
      | roger    |

    When I am logged in as glenn
    And I am on the homepage
    When I click "My account"
    And I click "Messages"
    Then I should see the heading "Messages"

    Given I click "New conversation"
    And I fill in "Title" with "Let's discuss about weather"
    And I fill in "Participants" with "ian"
    When I press "Add another user"
    And I fill in "edit-field-thread-participants-1-target-id" with "david"

    When I press "Create"
    Then I should see the following links:
      | Send a message |
      | david          |
      | glenn          |
      | ian            |

    Given I am logged in as ian
    And I am on the homepage
    When I click "My account"
    And I click "Messages"
    Then I should see the heading "Messages"
    And I should see the link "Let's discuss about weather"

    When I click "Let's discuss about weather"
    Then I should see the following links:
      | Send a message |
      | david          |
      | glenn          |
      | ian            |

    Given I click "Send a message"
    When I fill in "Body" with "Seems sunny"
    And I press "Create"
    Then I should see the heading "Let's discuss about weather"
    And I should see the following links:
      | Reply |
      | david |
      | glenn |
      | ian   |
    And I should see "Seems sunny"
    And I should see "ian" in the "Seems sunny" row

    Given I am logged in as david
    And I am on the homepage
    When I click "My account"
    And I click "Messages"
    Then I should see the heading "Messages"
    And I should see the link "Let's discuss about weather"

    When I click "Let's discuss about weather"
    Then I should see the following links:
      | Reply |
      | david |
      | glenn |
      | ian   |

    When I click Reply
    And I fill in "Body" with "Nope, it will rain"
    And I press "Create"

    Then I should see the heading "Let's discuss about weather"
    And I should see the following links:
      | Reply |
      | david |
      | glenn |
      | ian   |
    And I should see "ian" in the "Seems sunny" row
    And I should see "david" in the "Nope, it will rain" row

    Given I am logged in as roger
    And I am on the homepage
    When I click "My account"
    And I click "Messages"
    Then I should see the heading "Messages"
    But I should not see the link "Let's discuss about weather"
