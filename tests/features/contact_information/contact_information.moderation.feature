@api
Feature: Contact Information moderation
  In order to manage contact information
  As a facilitator or moderator
  I need to be able to transit the contact information from one state to another

  Scenario: Publish, update, request changes, publish again and ask to delete contact information.
    Given users:
      | name            |
      | Sæwine Cynebald |
    And owner:
      | type                          | name               |
      | Non-Governmental Organisation | Anglo-Saxon Museum |
    And collection:
      | title         | Games of the Anglo-Saxon age                  |
      | description   | There were many different board games played. |
      | logo          | logo.png                                      |
      | banner        | banner.jpg                                    |
      | owner         | Anglo-Saxon Museum                            |
      | policy domain | Demography                                    |
      | state         | draft                                         |
    And collection user membership:
      | collection                   | user            | roles       |
      | Games of the Anglo-Saxon age | Sæwine Cynebald | facilitator |

    # Add contact information to the collection as a facilitator.
    When I am logged in as "Sæwine Cynebald"
    And I go to the homepage of the "Games of the Anglo-Saxon age" collection
    And I click "Edit" in the "Entity actions" region
    And I click the 'Description' tab
    And I press "Add new" at the "Contact information" field
    And I fill in "Name" with "Mildþryð Mildgyð"
    And I fill in "E-mail address" with "mildred@anglo-saxon-museum.co.uk"
    And I press "Create contact information"
    Then I should see "Mildþryð Mildgyð"
    When I press "Save as draft"
    Then I should see the heading "Games of the Anglo-Saxon age"

    # Try changing the details of the contact information entity.
    When I go to the "Mildþryð Mildgyð" contact information page
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Contact information Mildþryð Mildgyð"
    And the following 2 buttons should be present "Update, Request deletion"
    And the current workflow state should be "Validated"
    And I should not see the link "Delete"
    When I fill in "Name" with "Ceolwulf II of Mercia"
    And I press "Update"
    Then I should see the heading "Ceolwulf II of Mercia"

    # Request an update as moderator: Ceolwulf II is deceased.
    When I am logged in as a moderator
    When I go to the "Ceolwulf II of Mercia" contact information page
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Contact information Ceolwulf II of Mercia"
    And the following 3 buttons should be present "Update, Request changes, Request deletion"
    And the current workflow state should be "Validated"
    # A moderator has the right to delete contact information directly, so this
    # action should be shown.
    And I should see the link "Delete"
    When I press "Request changes"
    Then I should see the heading "Ceolwulf II of Mercia"

    # Another authenticated user should not be allowed to edit the contact
    # information entity.
    When I am logged in as an "authenticated user"
    And I go to the "Ceolwulf II of Mercia" contact information page
    Then I should not see the link "Edit" in the "Entity actions" region

    # The original author is allowed to update the entity with the requested
    # changes.
    Given I am logged in as "Sæwine Cynebald"
    And I go to the "Ceolwulf II of Mercia" contact information page
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Contact information Ceolwulf II of Mercia"
    And the following 1 button should be present "Update"
    And the current workflow state should be "In assessment"
    And I should not see the link "Delete"
    # Do the changes.
    When I fill in "Name" with "Æthelred, Lord of the Mercians"
    And I press "Update"
    Then I should see the heading "Æthelred, Lord of the Mercians"

    # The moderator approves the changes.
    Given I am logged in as a moderator
    And I go to the "Æthelred, Lord of the Mercians" contact information page
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Approve changes"
    And the current workflow state should be "In assessment"
    And I press "Approve changes"
    Then I should see the heading "Æthelred, Lord of the Mercians"

    # The facilitator can request deletion.
    Given I am logged in as "Sæwine Cynebald"
    And I go to the "Æthelred, Lord of the Mercians" contact information page
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Request deletion"
    And the current workflow state should be "Validated"
    And I press "Request deletion"
    Then I should see the heading "Æthelred, Lord of the Mercians"

    # A moderator is able to approve the deletion.
    Given I am logged in as a moderator
    And I go to the "Æthelred, Lord of the Mercians" contact information page
    When I click "Edit" in the "Entity actions" region
    Then I should see the link "Delete"
    When I click "Delete"
    # Confirm the deletion.
    And I press "Delete"
    Then I should not see the link "EU healthy group"
