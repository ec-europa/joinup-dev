@api @email
Feature: Collection moderation
  In order to manage collections programmatically
  As a user of the website
  I need to be able to transit the collections from one state to another.

  # Access checks are not being made here. They are run in the collection add feature.
  Scenario: 'Draft' and 'Propose' states are available but moderators should also see 'Validated' state.
    When I am logged in as an "authenticated user"
    And I go to the propose collection form
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Request deletion, Archive"

    When I am logged in as a user with the "moderator" role
    And I go to the propose collection form
    Then the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request archival, Request deletion, Archive"

  Scenario: Test the moderation workflow available states.
    Given the following owner:
      | name           |
      | Simon Sandoval |
    And the following contact:
      | name  | Francis             |
      | email | Francis@example.com |
    And users:
      | Username        | Roles     |
      # Authenticated user.
      | Velma Smith     |           |
      # Moderator.
      | Lena Richardson | moderator |
      # Owner of all the collections.
      | Erika Reid      |           |
      # Facilitator of all the collections.
      | Carole James    |           |
    And the following collections:
      | title                   | description             | logo     | banner     | owner          | contact information | state            |
      | Deep Past               | Azure ship              | logo.png | banner.jpg | Simon Sandoval | Francis             | draft            |
      | The Licking Silence     | The Licking Silence     | logo.png | banner.jpg | Simon Sandoval | Francis             | proposed         |
      | Person of Wizards       | Person of Wizards       | logo.png | banner.jpg | Simon Sandoval | Francis             | validated        |
      | The Shard's Hunter      | The Shard's Hunter      | logo.png | banner.jpg | Simon Sandoval | Francis             | archival request |
      | The Dreams of the Mists | The Dreams of the Mists | logo.png | banner.jpg | Simon Sandoval | Francis             | deletion request |
      | Luck in the Abyss       | Luck in the Abyss       | logo.png | banner.jpg | Simon Sandoval | Francis             | archived         |
    And the following collection user memberships:
      | collection              | user         | roles       |
      | Deep Past               | Erika Reid   | owner       |
      | The Licking Silence     | Erika Reid   | owner       |
      | Person of Wizards       | Erika Reid   | owner       |
      | The Shard's Hunter      | Erika Reid   | owner       |
      | The Dreams of the Mists | Erika Reid   | owner       |
      | Luck in the Abyss       | Erika Reid   | owner       |
      | Deep Past               | Carole James | facilitator |
      | The Licking Silence     | Carole James | facilitator |
      | Person of Wizards       | Carole James | facilitator |
      | The Shard's Hunter      | Carole James | facilitator |
      | The Dreams of the Mists | Carole James | facilitator |
      | Luck in the Abyss       | Carole James | facilitator |

    # The following table tests the allowed transitions in a collection.
    # For each entry, the following steps must be performed:
    # Login with the given user (or a user with the same permissions).
    # Go to the homepage of the given collection.
    # If the expected states (states column) are empty, I should not have access
    # to the edit screen.
    # If the expected states are not empty, then I see the "Edit" link.
    # When I click the "Edit" link
    # Then the state field should have only the given states available.
    Then for the following collection, the corresponding user should have the corresponding available state buttons:
      | collection              | user            | states                                                     |

      # The owner is also a facilitator so the only
      # UATable part of the owner is that he has the ability to request deletion
      # or archival when the collection is validated.
      | Deep Past               | Erika Reid      | Save as draft, Propose                                     |
      | The Licking Silence     | Erika Reid      | Save as draft, Propose                                     |
      | Person of Wizards       | Erika Reid      | Save as draft, Propose, Request archival, Request deletion |
      | The Shard's Hunter      | Erika Reid      |                                                            |
      | The Dreams of the Mists | Erika Reid      |                                                            |
      | Luck in the Abyss       | Erika Reid      |                                                            |

      # The following collections do not follow the rule above and should be
      # testes as shown.
      | Deep Past               | Carole James    | Save as draft, Propose                                     |
      | The Licking Silence     | Carole James    | Save as draft, Propose                                     |
      | Person of Wizards       | Carole James    | Save as draft, Propose                                     |
      | The Shard's Hunter      | Carole James    |                                                            |
      | The Dreams of the Mists | Carole James    |                                                            |
      | Luck in the Abyss       | Carole James    |                                                            |
      | Deep Past               | Velma Smith     |                                                            |
      | The Licking Silence     | Velma Smith     |                                                            |
      | Person of Wizards       | Velma Smith     |                                                            |
      | The Shard's Hunter      | Velma Smith     |                                                            |
      | The Dreams of the Mists | Velma Smith     |                                                            |
      | Luck in the Abyss       | Velma Smith     |                                                            |
      | Deep Past               | Lena Richardson | Save as draft, Propose, Publish                            |
      | The Licking Silence     | Lena Richardson | Save as draft, Propose, Publish                            |
      | Person of Wizards       | Lena Richardson | Save as draft, Propose, Publish                            |
      | The Shard's Hunter      | Lena Richardson | Publish, Archive                                           |
      | The Dreams of the Mists | Lena Richardson | Publish                                                    |
      | Luck in the Abyss       | Lena Richardson |                                                            |

    # Authentication sample checks.
    Given I am logged in as "Carole James"

    # Expected access.
    And I go to the "Deep Past" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Request deletion, Archive"

    # Expected access.
    When I go to the "The Licking Silence" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Request deletion, Archive"

    # One check for the moderator.
    Given I am logged in as "Lena Richardson"
    # Expected access.
    And I go to the "Deep Past" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request archival, Request deletion, Archive"

  @terms
  Scenario: Published collections should be shown in the collections overview page.
    # Regression test for ticket ISAICP-2889.
    Given the following owner:
      | name             | type    |
      | Carpet Sandation | Company |
    And the following contact:
      | name  | Partyanimal             |
      | email | partyanimal@example.com |
    And collection:
      | title               | Some berry pie     |
      | description         | Berries are tasty. |
      | logo                | logo.png           |
      | banner              | banner.jpg         |
      | owner               | Carpet Sandation   |
      | contact information | Partyanimal        |
      | policy domain       | Supplier exchange  |
      | state               | proposed           |
    When I am on the homepage
    And I click "Collections"
    Then I should not see the heading "Some berry pie"
    When I am logged in as a moderator
    And I am on the homepage
    And I click "Collections"
    Then I should not see the text "Some berry pie"

    When I go to my dashboard
    Then I should see the "Some berry pie" tile
    When I go to the homepage of the "Some berry pie" collection
    And I click "Edit"
    And I fill in "Title" with "No berry pie"
    And I press "Publish"
    Then I should see the heading "No berry pie"

    When I am on the homepage
    And I click "Collections"
    Then I should see the text "No berry pie"
    And I should not see the text "Some berry pie"

  @terms @javascript
  Scenario: Moderate an open collection
    # Regression test for a bug that caused the slider that controls the
    # eLibrary creation setting to revert to default state when the form is
    # resubmitted, as happens during moderation. Ref. ISAICP-3200.
    Given I am logged in as a user with the "authenticated" role
    # Propose a collection, filling in the required fields.
    When I go to the propose collection form
    And I fill in "Title" with "Spectres in fog"
    And I enter "The samurai are attacking the railroads" in the "Description" wysiwyg editor
    And I select "Employment and Support Allowance" from "Policy domain"
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Katsumoto"
    And I check the box "Academia/Scientific organisation"
    And I click the "Additional fields" tab
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish

    # Configure eLibrary creation for all registered users.
    When I move the "eLibrary creation" slider to the right
    Then the option "Any registered user can create new content." should be selected

    # Regression test for a bug that caused the eLibrary creation setting to be
    # lost when adding an item to a multivalue field. Ref. ISAICP-3200.
    When I press "Add another item" at the "Spatial coverage" field
    And I wait for AJAX to finish
    Then the option "Any registered user can create new content." should be selected

    # Submit the form and approve it as a moderator. This should not cause the
    # eLibrary creation option to change.
    When I press "Propose"
    Then I should see the heading "Spectres in fog"
    When I am logged in as a user with the "moderator" role
    And I go to the homepage of the "Spectres in fog" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Any registered user can create new content." should be selected
    # Also when saving and reopening the edit form the eLibrary creation option
    # should remain unchanged.
    When I press "Publish"
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Any registered user can create new content." should be selected

    # Clean up the entities that were created.
    Then I delete the "Spectres in fog" collection
    Then I delete the "Katsumoto" owner

  @terms @javascript
  Scenario: Changing eLibrary creation value - regression #1
    # Regression test for a bug that happens when a change on the eLibrary
    # creation setting happens after an ajax callback.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in "Title" with "Domestic bovins"
    And I enter "Yaks and goats are friendly pets." in the "Description" wysiwyg editor
    And I select "Statistics and Analysis" from "Policy domain"
    # An ajax callback is executed now.
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Garnett Clifton"
    And I check the box "Supra-national authority"
    And I click the "Additional fields" tab
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish

    # Configure eLibrary creation for all registered users.
    When I move the "eLibrary creation" slider to the right
    Then the option "Any registered user can create new content." should be selected

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Domestic bovins"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Any registered user can create new content." should be selected

    # Clean up the entities that were created.
    Then I delete the "Domestic bovins" collection
    Then I delete the "Garnett Clifton" owner

  @terms @javascript
  Scenario: Changing eLibrary creation value - regression #2
    # Regression test for a bug that causes the wrong eLibrary creation value
    # to be saved after the "Closed collection" checkbox is checked.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in "Title" with "Theft of Body"
    And I enter "Kleptomaniac to the bone." in the "Description" wysiwyg editor
    And I select "Supplier exchange" from "Policy domain"
    # An ajax callback is executed now.
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Coretta Simonson"
    And I check the box "Private Individual(s)"
    And I click the "Additional fields" tab
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish

    When I check "Closed collection"
    And I wait for AJAX to finish

    # Configure eLibrary creation for all registered users.
    When I move the "eLibrary creation" slider to the right
    Then the option "Only collection facilitators can create new content." should be selected

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Theft of Body"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Only collection facilitators can create new content." should be selected

    # Clean up the entities that were created.
    Then I delete the "Theft of Body" collection
    Then I delete the "Coretta Simonson" owner

  @terms @javascript
  Scenario: Changing eLibrary creation value - regression #3
    # Regression test for a bug that happens when an "Add more" button on a
    # multi-value widget is clicked and then the "Closed collection" checkbox
    # is checked.
    # @see collection_form_rdf_entity_form_alter()
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in "Title" with "Silken Emperor"
    And I enter "So smooth." in the "Description" wysiwyg editor
    And I select "Data gathering, data processing" from "Policy domain"
    # An ajax callback is executed now.
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Terrance Nash"
    And I check the box "Regional authority"
    And I click the "Additional fields" tab
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish
    When I press "Add another item" at the "Spatial coverage" field
    And I wait for AJAX to finish

    When I check "Closed collection"
    And I wait for AJAX to finish

    # Configure eLibrary creation for all registered users.
    When I move the "eLibrary creation" slider to the right
    Then the option "Only collection facilitators can create new content." should be selected

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Silken Emperor"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Only collection facilitators can create new content." should be selected

    # Clean up the entities that were created.
    Then I delete the "Silken Emperor" collection
    Then I delete the "Terrance Nash" owner

  @terms @javascript
  Scenario: Changing eLibrary creation value - regression #4
    # Regression test for a bug that happens when the "Closed collection" checkbox
    # is checked and then an "Add more" button on a multi-value widget is clicked.
    # @see collection_form_rdf_entity_form_alter()
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in "Title" with "The blue ships"
    And I enter "Invisible ships on deep sea." in the "Description" wysiwyg editor
    And I select "Employment and Support Allowance" from "Policy domain"
    # An ajax callback is executed now.
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Mable Pelley"
    And I check the box "National authority"
    And I click the "Additional fields" tab
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish

    When I check "Closed collection"
    And I wait for AJAX to finish

    # Configure eLibrary creation for all registered users.
    When I move the "eLibrary creation" slider to the right
    Then the option "Only collection facilitators can create new content." should be selected

    When I press "Add another item" at the "Spatial coverage" field
    And I wait for AJAX to finish

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "The blue ships"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the option "Only collection facilitators can create new content." should be selected

    # Clean up the entities that were created.
    Then I delete the "The blue ships" collection
    Then I delete the "Mable Pelley" owner
