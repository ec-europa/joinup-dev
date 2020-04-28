@api @terms
Feature:
  As an owner of a group
  In order to make the URL of my group easier to read
  I need to be able to provide a short ID to my group.

  Scenario: Short IDs are unique in collections.
    Given the following collections:
      | title                     | short ID | state     |
      | Scientific Studies        | SSt-2020 | validated |
      | Scientific Sustainability | SST-2020 | validated |
    And owner:
      | name                 | type    |
      | Organisation example | Company |

    When I am logged in as an "authenticated user"
    And I go to the propose collection form

    # Assert the Short ID description is present.
    Then I should see the following lines of text:
      | Short URL is an abbreviated reference (It serves as a "nickname") for the collection, like an acronym. E.g. for a collection named "European Commission" it can be "EuCo". This value will be displayed in your browser's address bar. |
      | This is not editable after the collection's initial publication.                                                                                                                                                                       |
      | The value must be between 4 and 26 characters and can contain only alphanumeric characters and the dash(-) symbol.                                                                                                                     |

    When I press "Add existing" at the "Owner" field
    And I fill in the following:
      | Title       | Structural Solar Traces    |
      | Description | Structural Solar Traces    |
      | Owner       | Organisation example       |
      # Contact information data.
      | Name        | Contact person             |
      | E-mail      | contact_person@example.com |
    And I select "HR" from "Policy domain"
    And I fill in "Short ID" with "SST-2020"
    And I press "Propose"
    Then I should see the error message "Content with short id SST-2020 already exists. Please choose a different short id."

    And I fill in "Short ID" with "Sst-2020"
    And I press "Propose"
    # The short id is case insensitive.
    Then I should see the error message "Content with short id Sst-2020 already exists. Please choose a different short id."

    And I fill in "Short ID" with "SsTr-2020"
    And I press "Propose"
    And I should see the heading "Structural Solar Traces"

    When I go to the "Structural Solar Traces" collection edit form
    Then the following fields should not be disabled "Short ID"

    # Publish the collection.
    When I am logged in as a moderator
    And I go to the "Structural Solar Traces" collection edit form
    And I press "Publish"
    Then I should see the heading "Structural Solar Traces"

    # Check that the "Short ID" field is not disabled for moderators.
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be disabled "Short ID"

    # Check that the field is not editable for facilitators.
    When I am logged in as a facilitator of the "Structural Solar Traces" collection
    And I go to the "Structural Solar Traces" collection edit form
    Then the following fields should be disabled "Short ID"

    Then I delete the "Structural Solar Traces" collection
    And I delete the "Contact person" contact information

  Scenario: Short IDs are unique in solutions.
    Given the following collections:
      | title              | short ID | state     |
      | Scientific Studies | SST-2020 | validated |
    And solutions:
      | title                         | short ID | state     | collection         |
      | Who knows what a solution is? | WKWaSi   | validated | Scientific Studies |
    And owner:
      | name                 | type    |
      | Organisation example | Company |

    When I am logged in as a facilitator of the "Scientific Studies" collection
    And I go to the homepage of the "Scientific Studies" collection
    And I click "Add solution"

    # Assert the Short ID description.
    Then I should see the following lines of text:
      | Short URL is an abbreviated reference (It serves as a "nickname") for the solution, like an acronym. E.g. for a solution named "European Commission" it can be "EuCo". This value will be displayed in your browser's address bar. |
      | This is not editable after the solution's initial publication.                                                                                                                                                                     |
      | The value must be between 4 and 26 characters and can contain only alphanumeric characters and the dash(-) symbol.                                                                                                                 |

    When I press "Add existing" at the "Owner" field
    And I fill in the following:
      | Title          | I know what a solution is |
      | Description    | Dummy text                |
      # Contact information details.
      | Name           | John Smith                |
      | E-mail address | john.smith@example.com    |
      # Existing owner.
      | Owner          | Organisation example      |
    Then I select "http://data.europa.eu/dr8/DataExchangeService" from "Solution type"
    And I select "Demography" from "Policy domain"

    # Short ID is case insensitive.
    And I fill in "Short ID" with "WKWASI"
    And I press "Propose"
    Then I should see the error message "Content with short id WKWASI already exists. Please choose a different short id."

    # The short ID is only unique among solutions.
    And I fill in "Short ID" with "SST-2020"
    And I press "Propose"
    Then I should see the heading "I know what a solution is"

    When I go to the "I know what a solution is" solution edit form
    Then the following fields should not be disabled "Short ID"

    # Publish the solution.
    When I am logged in as a moderator
    And I go to the "I know what a solution is" solution edit form
    And I press "Publish"
    Then I should see the heading "I know what a solution is"

    # Check that the "Short ID" field is not disabled for moderators.
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be disabled "Short ID"

    # Check that the field is not editable for facilitators.
    When I am logged in as a facilitator of the "I know what a solution is" solution
    And I go to the "I know what a solution is" solution edit form
    Then the following fields should be disabled "Short ID"

    Then I delete the "I know what a solution is" solution
    And I delete the "John Smith" contact information
