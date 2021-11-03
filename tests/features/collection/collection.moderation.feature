@api @group-a
Feature: Collection moderation
  In order to manage collections programmatically
  As a user of the website
  I need to be able to transit the collections from one state to another.

  # Access checks are not being made here. They are run in the collection add feature.
  Scenario: 'Draft' and 'Propose' states are available but moderators should also see 'Validated' state.
    When I am logged in as an "authenticated user"
    And I go to the propose collection form
    Then the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Archive"
    And I should not see the link "Delete"

    When I am logged in as a user with the "moderator" role
    And I go to the propose collection form
    Then the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request archival, Archive"
    And I should not see the link "Delete"

  Scenario: Test the available buttons in every stage of the editorial workflow
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
      | title               | description         | logo     | banner     | owner          | contact information | state            |
      | Deep Past           | Azure ship          | logo.png | banner.jpg | Simon Sandoval | Francis             | draft            |
      | The Licking Silence | The Licking Silence | logo.png | banner.jpg | Simon Sandoval | Francis             | proposed         |
      | Person of Wizards   | Person of Wizards   | logo.png | banner.jpg | Simon Sandoval | Francis             | validated        |
      | The Shard's Hunter  | The Shard's Hunter  | logo.png | banner.jpg | Simon Sandoval | Francis             | archival request |
      | Luck in the Abyss   | Luck in the Abyss   | logo.png | banner.jpg | Simon Sandoval | Francis             | archived         |
    And the following collection user memberships:
      | collection          | user         | roles       |
      | Deep Past           | Erika Reid   | owner       |
      | The Licking Silence | Erika Reid   | owner       |
      | Person of Wizards   | Erika Reid   | owner       |
      | The Shard's Hunter  | Erika Reid   | owner       |
      | Luck in the Abyss   | Erika Reid   | owner       |
      | Deep Past           | Carole James | facilitator |
      | The Licking Silence | Carole James | facilitator |
      | Person of Wizards   | Carole James | facilitator |
      | The Shard's Hunter  | Carole James | facilitator |
      | Luck in the Abyss   | Carole James | facilitator |

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
      | collection          | user            | buttons                                           |

      # The owner is also a facilitator so the only UATable part of the owner is that they have the ability to
      # request archival and delete the collection when the collection is validated.
      | Deep Past           | Erika Reid      | Save as draft, Propose                            |
      | The Licking Silence | Erika Reid      | Propose, Save as draft                            |
      # Person of Wizards has a published version so the facilitator can publish directly.
      # The facilitator can still save as draft or propose for internal checking between eligible users.
      # Note that the 'Delete' action is represented as a link rather than a button and has a dedicated test below.
      | Person of Wizards   | Erika Reid      | Publish, Save as draft, Propose, Request archival |
      | The Shard's Hunter  | Erika Reid      |                                                   |
      | Luck in the Abyss   | Erika Reid      |                                                   |

      # The following collections do not follow the rule above and should be
      # tested as shown.
      | Deep Past           | Carole James    | Save as draft, Propose                            |
      | The Licking Silence | Carole James    | Propose, Save as draft                            |
      | Person of Wizards   | Carole James    | Publish, Save as draft, Propose                   |
      | The Shard's Hunter  | Carole James    |                                                   |
      | Luck in the Abyss   | Carole James    |                                                   |
      | Deep Past           | Velma Smith     |                                                   |
      | The Licking Silence | Velma Smith     |                                                   |
      | Person of Wizards   | Velma Smith     |                                                   |
      | The Shard's Hunter  | Velma Smith     |                                                   |
      | Luck in the Abyss   | Velma Smith     |                                                   |
      | Deep Past           | Lena Richardson | Save as draft, Propose, Publish                   |
      | The Licking Silence | Lena Richardson | Propose, Save as draft, Publish                   |
      | Person of Wizards   | Lena Richardson | Publish, Save as draft, Propose                   |
      | The Shard's Hunter  | Lena Richardson | Publish, Archive                                  |
      | Luck in the Abyss   | Lena Richardson |                                                   |

    # The 'Delete' action is not a button but a link leading to a confirmation
    # page that is styled as a button. It should only be available to the owner
    # of a validated collection.
    And the visibility of the delete link should be as follows for these users in these collections:
      | collection          | user            | delete link |
      | Person of Wizards   | Erika Reid      | yes         |
      | The Shard's Hunter  | Erika Reid      | no          |
      | Luck in the Abyss   | Erika Reid      | no          |
      | Deep Past           | Carole James    | no          |
      | The Licking Silence | Carole James    | no          |
      | Person of Wizards   | Carole James    | no          |
      | The Shard's Hunter  | Carole James    | no          |
      | Luck in the Abyss   | Carole James    | no          |
      | Deep Past           | Velma Smith     | no          |
      | The Licking Silence | Velma Smith     | no          |
      | Person of Wizards   | Velma Smith     | no          |
      | The Shard's Hunter  | Velma Smith     | no          |
      | Luck in the Abyss   | Velma Smith     | no          |
      # A moderator can also see the delete link.
      | Deep Past           | Lena Richardson | yes         |
      | The Licking Silence | Lena Richardson | yes         |
      | Person of Wizards   | Lena Richardson | yes         |
      | The Shard's Hunter  | Lena Richardson | yes         |
      | Luck in the Abyss   | Lena Richardson | yes         |

    # Authentication sample checks.
    Given I am logged in as "Carole James"

    # Expected access.
    And I go to the "Deep Past" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Archive"
    And I should not see the link "Delete"

    # Expected access.
    When I go to the "The Licking Silence" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request archival, Archive"
    And I should not see the link "Delete"

    # One check for the moderator.
    Given I am logged in as "Lena Richardson"
    # Expected access.
    And I go to the "Deep Past" collection
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Propose, Publish"
    And the following buttons should not be present "Request archival, Archive"
    # The delete button is actually a link that is styled to look like a button.
    And I should see the link "Delete"

    # Check that the owner can delete their own collection.
    Given I am logged in as "Erika Reid"
    And I go to the "Person of Wizards" collection
    When I click "Edit"
    And I click "Delete"
    Then I should see the heading "Are you sure you want to delete collection Person of Wizards?"
    And I should see "This action cannot be undone."
    When I press "Delete"
    # @todo: check that a success message is shown.
    # @see ISAICP-6140
    Then I should be on the homepage

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
      | topic               | Supplier exchange  |
      | state               | proposed           |
    When I visit the collection overview
    Then I should not see the heading "Some berry pie"
    When I am logged in as a moderator
    And I visit the collection overview
    Then I should not see the text "Some berry pie"

    When I go to my dashboard
    Then I should see the "Some berry pie" tile
    When I go to the homepage of the "Some berry pie" collection
    And I click "Edit"
    And I fill in "Title" with "No berry pie"
    And I press "Publish"
    Then I should see the heading "No berry pie"

    When I visit the collection overview
    Then I should see the text "No berry pie"
    And I should not see the text "Some berry pie"

  @javascript @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Moderate an open collection
    # Regression test for a bug that caused the slider that controls the
    # content creation setting to revert to default state when the form is
    # resubmitted, as happens during moderation. Ref. ISAICP-3200.
    # Note that this is an issue that affected the legacy eLibrary slider which
    # has been replaced with the Content creation radio buttons, but we are
    # keeping the coverage for now.
    Given I am logged in as a user with the "authenticated" role
    # Propose a collection, filling in the required fields.
    When I go to the propose collection form
    Then the "Main fields" tab should be active
    And the "Main fields" tab summary should be "Contains all the fields to be mandatorily filled to create a collection"
    And the "Additional fields" tab summary should be "Contains all optional fields providing additional information on the collection"
    And I fill in the following:
      | Title  | Spectres in fog        |
      # Contact information data.
      | Name   | A secretary in the fog |
      | E-mail | fog@example.com        |
    And I enter "The samurai are attacking the railroads" in the "Description" wysiwyg editor
    And I select "Employment and Support Allowance" from "Topic"
    And I press "Add new" at the "Owner" field
    And I wait for AJAX to finish
    And I fill in "Name" with "Katsumoto"
    And I check the box "Academia/Scientific organisation"
    And I click the "Additional fields" tab
    Then the "Additional fields" tab should be active
    And I attach the file "logo.png" to "Logo"
    And I wait for AJAX to finish
    And I attach the file "banner.jpg" to "Banner"
    And I wait for AJAX to finish
    And I select the radio button "Any user can create content."

    # Regression test for a bug that caused the content creation setting to be
    # lost when adding an item to a multivalue field. Ref. ISAICP-3200.
    # Note that this is an issue that affected the legacy eLibrary slider which
    # has been replaced with the Content creation radio buttons, but we are
    # keeping the coverage for now.
    When I press "Add another item" at the "Geographical coverage" field
    And I wait for AJAX to finish
    Then the radio button "Any user can create content." from field "Content creation" should be selected

    # Submit the form and approve it as a moderator. This should not cause the
    # content creation option to change.
    When I press "Propose"
    Then I should see the heading "Spectres in fog"
    When I am logged in as a user with the "moderator" role
    And I go to the homepage of the "Spectres in fog" collection
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Any user can create content." from field "Content creation" should be selected
    # Also when saving and reopening the edit form the content creation option
    # should remain unchanged.
    When I press "Publish"
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Any user can create content." from field "Content creation" should be selected

    # Clean up the entities that were created.
    Then I delete the "Spectres in fog" collection
    And I delete the "Katsumoto" owner
    And I delete the "A secretary in the fog" contact information

  @javascript @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Changing Content creation value - regression #1
    # Regression test for a bug that happens when a change on the content
    # creation setting happens after an ajax callback.
    # Note that this is an issue that affected the legacy eLibrary slider which
    # has been replaced with the Content creation radio buttons, but we are
    # keeping the coverage for now.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in the following:
      | Title  | Domestic bovins    |
      # Contact information data.
      | Name   | Domestic secretary |
      | E-mail | ds@example.com     |
    And I enter "Yaks and goats are friendly pets." in the "Description" wysiwyg editor
    And I select "Statistics and Analysis" from "Topic"
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
    And I select the radio button "Any user can create content."

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Domestic bovins"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Any user can create content." from field "Content creation" should be selected

    # Clean up the entities that were created.
    Then I delete the "Domestic bovins" collection
    And I delete the "Garnett Clifton" owner
    And I delete the "Domestic secretary" contact information

  @javascript @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Changing Content creation value - regression #2
    # Regression test for a bug that causes the wrong content creation value
    # to be saved after the "Closed collection" checkbox is checked.
    # Note that the "Closed collection" option no longer exists and that the
    # legacy eLibrary slider has been replaced with the Content creation radio
    # buttons, but we are keeping the coverage for now.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in the following:
      | Title  | Theft of Body        |
      # Contact information data.
      | Name   | Secretary of thieves |
      | E-mail | st@example.com       |
    And I enter "Kleptomaniac to the bone." in the "Description" wysiwyg editor
    And I select "Supplier exchange" from "Topic"
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
    And I select the radio button "Only facilitators and authors can create content."

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Theft of Body"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Only facilitators and authors can create content." from field "Content creation" should be selected

    # Clean up the entities that were created.
    Then I delete the "Theft of Body" collection
    And I delete the "Coretta Simonson" owner
    And I delete the "Secretary of thieves" contact information

  @javascript @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Changing Content creation value - regression #3
    # Regression test for a bug that happens when an "Add more" button on a
    # multi-value widget is clicked and then the "Closed collection" checkbox
    # is checked.
    # @see collection_form_rdf_entity_form_alter()
    # Note that the "Closed collection" option no longer exists but we are
    # keeping the coverage for now.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in the following:
      | Title  | Silken Emperor    |
      # Contact information data.
      | Name   | Secretary of Silk |
      | E-mail | ss@example.com    |
    And I enter "So smooth." in the "Description" wysiwyg editor
    And I select "Data gathering, data processing" from "Topic"
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
    When I press "Add another item" at the "Geographical coverage" field
    And I wait for AJAX to finish
    And I select the radio button "Only facilitators and authors can create content."

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "Silken Emperor"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Only facilitators and authors can create content." from field "Content creation" should be selected

    # Clean up the entities that were created.
    Then I delete the "Silken Emperor" collection
    And I delete the "Terrance Nash" owner
    And I delete the "Secretary of Silk" contact information

  @javascript @terms @uploadFiles:logo.png,banner.jpg
  Scenario: Changing Content creation value - regression #4
    # Regression test for a bug that happens when the "Closed collection" checkbox
    # is checked and then an "Add more" button on a multi-value widget is clicked.
    # @see collection_form_rdf_entity_form_alter()
    # Note that the "Closed collection" option no longer exists but we are
    # keeping the coverage for now.
    Given I am logged in as a user with the "authenticated" role
    When I go to the propose collection form
    And I fill in the following:
      | Title  | The blue ships          |
      # Contact information data.
      | Name   | Secretary of the harbor |
      | E-mail | sh@example.com          |
    And I enter "Invisible ships on deep sea." in the "Description" wysiwyg editor
    And I select "Employment and Support Allowance" from "Topic"
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
    And I select the radio button "Only facilitators and authors can create content."

    When I press "Add another item" at the "Geographical coverage" field
    And I wait for AJAX to finish

    # Save the collection.
    When I press "Propose"
    Then I should see the heading "The blue ships"
    # Edit again.
    When I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    And I click the "Additional fields" tab
    Then the radio button "Only facilitators and authors can create content." from field "Content creation" should be selected

    # Clean up the entities that were created.
    Then I delete the "The blue ships" collection
    And I delete the "Mable Pelley" owner
    And I delete the "Secretary of the harbor" contact information
