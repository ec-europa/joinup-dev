@api
Feature: "Add solution" visibility options.
  In order to manage solutions
  As a moderator
  I need to be able to add "Solution" rdf entities through UI.

  Scenario: "Add solution" button should only be shown to moderators and facilitators.
    Given the following collection:
      | title | Collection solution test |
      | logo  | logo.png                 |
      | state | validated                |

    When I am logged in as a moderator
    And I go to the homepage of the "Collection solution test" collection
    Then I should see the link "Add solution"

    When I am logged in as a "facilitator" of the "Collection solution test" collection
    And I go to the homepage of the "Collection solution test" collection
    Then I should see the link "Add solution"

    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"
    # Regression test to ensure that the user has not access to the 'Propose solution' page.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2842
    And I should not see the link "Propose solution"

    When I am an anonymous user
    And I go to the homepage of the "Collection solution test" collection
    Then I should not see the link "Add solution"

  @terms
  Scenario: Add solution as a collection facilitator.
    Given the following collection:
      | title | Belgian barista's |
      | logo  | logo.png          |
      | state | validated         |
    And the following owner:
      | name                 | type                         |
      | Organisation example | Company, Industry consortium |
    And I am logged in as a facilitator of the "Belgian barista's" collection

    When I go to the homepage of the "Belgian barista's" collection
    And I click "Add solution"
    Then I should see the heading "Add Solution"
    And the following fields should be present "Title, Description, Upload a new file or enter a URL, Logo, Banner, Name, E-mail address, Website URL"
    And the following fields should not be present "Groups audience, Other groups, Current workflow state, Langcode, Translation, Motivation"
    # Regression test for ensuring that obsolete eLibrary value is removed.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3567
    And I should not see the text "Only members can create new content"
    And I should see the text "Only solution facilitators can create new content"
    # Regression test to endure that the language terms "Multilingual Code" are not present.
    And the available options in the "Language" select should not include the "Multilingual Code"
    And I should see the description "For best result the image must be larger than 2400x770 pixels." for the "Banner" field
    And the "Solution type" field should contain the "IOP specification underpinning View, Legal View, Organisational View" option groups
    When I fill in the following:
      | Title            | Espresso is the solution                                      |
      | Description      | This is a test text                                           |
      | Spatial coverage | Belgium                                                       |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS |
      | Name             | Ernst Brice                                                   |
      | E-mail address   | ernsy1999@gmail.com                                           |
    Then I select "http://data.europa.eu/dr8/DataExchangeService" from "Solution type"
    And I select "Demography" from "Policy domain"
    # Attach a PDF to the documentation.
    And I upload the file "text.pdf" to "Upload a new file or enter a URL"
    # The owner field should have a help text.
    And I should see the text "The Owner is the organisation that owns this entity and is the only responsible for it."
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    # Ensure that the Status field is a dropdown.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3342
    And I select "Completed" from "Status"
    And I press "Propose"
    # Regression test for non required fields 'Banner' and 'Logo'.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3328
    Then I should not see the following error messages:
      | error messages            |
      | Banner field is required. |
      | Logo field is required.   |
    But I should see a logo on the header
    And I should see a banner on the header
    And I should see the heading "Espresso is the solution"
    When I am logged in as a moderator
    When I go to the "Espresso is the solution" solution edit form
    And I press "Publish"
    # The name of the solution should exist in the block of the relative content in a collection.
    Then I should see the heading "Espresso is the solution"
    # The solution fields will be shown in the "about" page.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3224
    And I should not see the text "This is a test text"
    And I should not see the link "Demography"
    And I should not see the link "Belgium"
    And I should not see the link "Flemish"

    When I am logged in as a facilitator of the "Belgian barista's" collection
    # Make sure that when another solution is added, both are affiliated.
    When I go to the homepage of the "Belgian barista's" collection
    And I click "Add solution"
    When I fill in the following:
      | Title            | V60 filter coffee solution                                             |
      | Description      | This is a test text                                                    |
      | Spatial coverage | Belgium (http://publications.europa.eu/resource/authority/country/BEL) |
      | Language         | http://publications.europa.eu/resource/authority/language/VLS          |
      | Name             | Ajit Tamboli                                                           |
      | E-mail address   | tambotamboli@gocloud.in                                                |
    Then I select "http://data.europa.eu/dr8/DataExchangeService" from "Solution type"
    And I select "E-inclusion" from "Policy domain"
    # Attach a PDF to the documentation.
    And I upload the file "text.pdf" to "Upload a new file or enter a URL"
    And I attach the file "logo.png" to "Logo"
    And I attach the file "banner.jpg" to "Banner"
    # Click the button to select an existing owner.
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Organisation example"
    And I press "Add owner"
    And I press "Propose"
    Then I should see the heading "V60 filter coffee solution"
    # The name of the solution should exist in the block of the relative content in a collection.
    When I go to the homepage of the "Belgian barista's" collection
    Then I should see the heading "Belgian barista's"
    And I should see the link "Espresso is the solution"
    # The proposed solution should not be visible since it's not yet validated.
    But I should not see the link "V60 filter coffee solution"

    When I visit "/user"
    Then I should see the link "V60 filter coffee solution"
    # Clean up the solutions that were created through the UI.
    Then I delete the "V60 filter coffee solution" solution
    Then I delete the "Espresso is the solution" solution

  @terms
  Scenario: Correct transition buttons are shown after a partial filled form is submitted.
    # This is a regression test for a bug that was causing the owner state
    # buttons to leak into the solution create form, causing critical errors
    # when an invalid state button was pressed.
    # @see issue ISAICP-3209
    Given the following collection:
      | title | Language parsers |
      | state | validated        |
    When I am logged in as a facilitator of the "Language parsers" collection
    And I go to the homepage of the "Language parsers" collection
    And I click "Add solution"
    And I fill in the following:
      | Title       | PHP comments parser                             |
      | Description | A simple parser that goes through PHP comments. |
    And I select "Data gathering, data processing" from "Policy domain"
    And I select "[ABB117] Implementing Guideline" from "Solution type"

    # Submit the incomplete form, so error messages about missing fields will
    # be shown.
    When I press "Propose"
    Then I should see the following error message:
      | error messages                    |
      | Name field is required.           |
      | E-mail address field is required. |

    # Fill the owner inline form, but don't submit it.
    When I press "Add new" at the "Owner" field
    And I fill in "Name" with "Azure Tennison"

    # Buttons should be shown for the allowed solution transitions.
    When I press "Propose"
    Then I should see the button "Save as draft"
    And I should see the button "Propose"
    # The owner entity state buttons should not be shown.
    But I should not see the button "Request deletion"
    And I should not see the button "Update"

  @terms
  Scenario: Create a solution with a name that already exists
    Given the following collections:
      | title              | state     |
      | Ocean studies      | validated |
      | Glacier monitoring | validated |
    And the following solution:
      | title             | Climate change tracker                            |
      | description       | Atlantic salmon arrived after the Little Ice Age. |
      | collection        | Ocean studies                                     |
      | state             | validated                                         |
    And the following owner:
      | name                | type                             |
      | University of Basel | Academia/Scientific organisation |

    # No two solutions with the same name may be created in the same collection.
    Given I am logged in as a member of the "Ocean studies" collection
    When I go to the homepage of the "Ocean studies" collection
    And I click "Add solution" in the plus button menu
    And I fill in "Title" with "Climate change tracker"
    And I press "Propose"
    Then I should see the error message "A solution titled Climate change tracker already exists in this collection. Please choose a different title."

    # If a solution with a duplicate name is created in a different collection
    # then this is allowed to be submitted but a warning should be shown to the
    # moderator when approving the proposal.
    Given I am logged in as a member of the "Glacier monitoring" collection
    When I go to the homepage of the "Glacier monitoring" collection
    And I click "Add solution" in the plus button menu
    And I fill in the following:
      | Title            | Climate change tracker                      |
      | Description      | Logs retreat of 40 glaciers in Switzerland. |
      | Spatial coverage | Switzerland                                 |
      | Name             | Angela Crespi                               |
      | E-mail address   | angela_crespi@glacmon.basel-uni.ch          |
    And I select "Data gathering, data processing" from "Policy domain"
    And I select "[ABB59] Logging Service" from "Solution type"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "University of Basel"
    And I press "Add owner"
    And I press "Propose"
    Then I should see the heading "Climate change tracker"
    # Check that the warning intended for moderators is not shown to regular
    # users.
    When I click "Edit" in the "Entity actions" region
    Then I should not see the warning message "A solution with the same name exists in a different collection."

    Given I am logged in as a moderator
    And I go to my dashboard
    And I click "Climate change tracker"
    And I click "Edit" in the "Entity actions" region
    Then I should see the warning message "A solution with the same name exists in a different collection."

    # Clean up the entities that were created through the UI. We have no control
    # over which of the two identically named solutions is deleted first, so
    # let's just get rid of both.
    Then I delete the "Climate change tracker" solution
    And I delete the "Climate change tracker" solution
    And I delete the "Angela Crespi" contact information
