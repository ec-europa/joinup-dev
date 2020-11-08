@api
Feature: Subscribing to a collection after joining
  In order to promote my collection
  As a collection owner
  I want to persuade new members to subscribe to my collection

  @javascript
  Scenario: Show a modal dialog asking a user to subscribe after joining
    Given collections:
      | title            | abstract                       | closed | description                          | state     |
      | Sapient Pearwood | Grows in magic-polluted areas  | no     | This tree is impervious to magic.    | validated |
      | Drop bears       | Predator from the koala family | no     | Drops from a tree onto its prey.     | validated |
      | Troll ducks      | Stone ducks don't float        | yes    | They sink to the bottom and walk.    | validated |
      | Swamp dragons    | Explode when overexcited       | yes    | Bred for the hotness of their flame. | validated |
    And users:
      | Username          |
      | Echinoid Blacksly |

    # Join an open collection.
    Given I am logged in as "Echinoid Blacksly"
    And I go to the homepage of the "Sapient Pearwood" collection
    When I press the "Join this collection" button
    Then I should see the success message "You are now a member of Sapient Pearwood."

    # A modal dialog opens proposing the user to subscribe to the collection.
    And a modal should open
    And I should see the text "Welcome to Sapient Pearwood" in the "Modal title"
    And I should see the text "You have joined the collection and you are now able to publish content in it." in the "Modal content"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the text "You can receive weekly notifications for this collection, by selecting the subscribe button below" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region

    # The user can decline the subscription.
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed
    And I should not be subscribed to the "Sapient Pearwood" collection

    # Navigate to another open collection, this time subscribing.
    When I go to the homepage of the "Drop bears" collection
    And I press the "Join this collection" button
    Then a modal should open
    When I press "Subscribe" in the "Modal buttons" region
    Then the modal should be closed
    And I should see the success message "You have been subscribed to Drop bears and will receive weekly notifications. To manage your notifications go to My subscriptions in your user menu."
    And I should have the following collection content subscriptions:
      | Drop bears | discussion, document, event, news |

    # Navigate to a closed collection and deny the subscription.
    When I go to the homepage of the "Troll ducks" collection
    And I press the "Join this collection" button
    Then a modal should open
    And I should see the text "Welcome to Troll ducks" in the "Modal title"
    And I should see the text "When your membership is approved you will be able to publish content in it." in the "Modal content"
    And I should see the text "Want to receive notifications, too?" in the "Modal content"
    And I should see the text "You can receive weekly notifications for this collection, by selecting the subscribe button below" in the "Modal content"
    And I should see the button "No thanks" in the "Modal buttons" region
    And I should see the button "Subscribe" in the "Modal buttons" region
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed
    And I should not be subscribed to the "Troll ducks" collection

    # Navigate to a closed collection and accept the subscription.
    When I go to the homepage of the "Swamp dragons" collection
    And I press the "Join this collection" button
    Then a modal should open
    When I press "Subscribe" in the "Modal buttons" region
    Then the modal should be closed
    And I should see the success message "You have been subscribed to Swamp dragons and will receive weekly notifications once your membership is approved."
    And I should have the following collection content subscriptions:
      | Swamp dragons | discussion, document, event, news |
