@api @terms @group-f
Feature: Contact Information moderation
  In order to manage contact information
  As a facilitator or moderator
  I need to be able to transit the contact information from one state to another

  Scenario: Publish, update, request changes, publish again and ask to delete contact information.
    Given users:
      | Username              |
      | Sæwine Cynebald       |
      | Secondary facilitator |
    And owner:
      | type                          | name               |
      | Non-Governmental Organisation | Anglo-Saxon Museum |
    And collection:
      | title       | Games of the Anglo-Saxon age                  |
      | description | There were many different board games played. |
      | logo        | logo.png                                      |
      | banner      | banner.jpg                                    |
      | owner       | Anglo-Saxon Museum                            |
      | topic       | E-inclusion                                   |
      | state       | draft                                         |
    And collection user membership:
      | collection                   | user                  | roles       |
      | Games of the Anglo-Saxon age | Sæwine Cynebald       | facilitator |
      | Games of the Anglo-Saxon age | Secondary facilitator | facilitator |

    # Add contact information to the collection as a facilitator.
    When I am logged in as "Sæwine Cynebald"
    And I go to the homepage of the "Games of the Anglo-Saxon age" collection
    And I click "Edit" in the "Entity actions" region
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
    And the following 1 button should be present "Update"
    And the current workflow state should be "Validated"
    And I should see the link "Delete"
    When I fill in "Name" with "Ceolwulf II of Mercia"
    And I press "Update"
    Then I should see the heading "Ceolwulf II of Mercia"

    # Another facilitator should be able to edit the contact entity.
    When I am logged in as "Secondary facilitator"
    And I go to the "Ceolwulf II of Mercia" contact information page
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Contact information Ceolwulf II of Mercia"
    And the following 1 button should be present "Update"
    And I should see the link "Delete"

    # Request an update as moderator: Ceolwulf II is deceased.
    When I am logged in as a moderator
    When I go to the "Ceolwulf II of Mercia" contact information page
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Contact information Ceolwulf II of Mercia"
    And the following fields should not be present "Langcode, Translation"
    And the following 2 buttons should be present "Update, Request changes"
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
    And the current workflow state should be "Needs update"
    And I should see the link "Delete"
    # Do the changes.
    When I fill in "Name" with "Æthelred, Lord of the Mercians"
    And I press "Update"
    Then I should see the heading "Æthelred, Lord of the Mercians"

    # The moderator approves the changes.
    Given I am logged in as a moderator
    And I go to the "Æthelred, Lord of the Mercians" contact information page
    And I click "Edit" in the "Entity actions" region
    And the following 2 buttons should be present "Update, Approve changes"
    And the current workflow state should be "Needs update"
    And I press "Approve changes"
    Then I should see the heading "Æthelred, Lord of the Mercians"

    # The facilitator can request deletion.
    Given I am logged in as "Sæwine Cynebald"
    And I go to the "Æthelred, Lord of the Mercians" contact information page
    And I click "Edit" in the "Entity actions" region
    And the following 1 buttons should be present "Update"
    And the current workflow state should be "Validated"
    And I should see the link "Delete"
    When I click "Delete"
    # Confirm the deletion.
    And I press "Delete"
    Then I should not see the link "EU healthy group"

  Scenario: Owners can request deletion when they are not facilitators and facilitators can delete.
    Given the following owner:
      | name        | type    |
      | Saint Louis | Company |
    Given users:
      | Username        | First name | Family name |
      | Sown Carnberry  | Sown       | Carnberry   |
      | Saint Louis CEO | George     | McLouis     |
    And the following contact:
      | name   | Secreteriat      |
      | email  | info@example.com |
      | author | Sown Carnberry   |
    And collection:
      | title               | Saint Louis solutions |
      | description         | A software company    |
      | logo                | logo.png              |
      | banner              | banner.jpg            |
      | owner               | Saint Louis           |
      | contact information | Secreteriat           |
      | state               | validated             |
    And the following collection user memberships:
      | collection            | user            | roles       |
      | Saint Louis solutions | Sown Carnberry  |             |
      | Saint Louis solutions | Saint Louis CEO | facilitator |

    When I am logged in as "Sown Carnberry"
    And I go to the "Secreteriat" contact information page
    And I click "Edit" in the "Entity actions"
    And the following 2 button should be present "Update, Request deletion"
    And I should not see the link "Delete"

    When I am logged in as "Saint Louis CEO"
    And I go to the "Secreteriat" contact information page
    And I click "Edit" in the "Entity actions"
    And the following 1 button should be present "Update"
    And I should see the link "Delete"
    When I go to the edit form of the "Saint Louis solutions" collection
    Then I should see the button "Edit" in the "Contact information inline form" region
    Then I should see the button "Remove" in the "Contact information inline form" region
