@api @terms @group-c
Feature:
  As an owner of a group
  In order to make the URL of my group easier to read
  I need to be able to provide a short ID to my group.

  Scenario: Short IDs are unique in collections.
    Given the following collections:
      | title              | short ID | state     |
      | Scientific Studies | sst-2020 | validated |
    And owner:
      | name                 | type    |
      | Organisation example | Company |

    When I am logged in as an "authenticated user"
    And I go to the propose collection form

    # Assert the Short ID description is present.
    Then I should see the following lines of text:
      | Used as the web address (URL). Example: a collection named 'European Commission' could use 'eu-commission'. |
      | Cannot be changed once the collection is published.                                                         |
      | The ID is between 4-26 characters and consists of lowercase letters, numbers and the dash symbol (-).       |

    When I press "Add existing" at the "Owner" field
    And I fill in the following:
      | Title       | Structural Solar Traces    |
      | Description | Structural Solar Traces    |
      | Owner       | Organisation example       |
      # Contact information data.
      | Name        | Contact person             |
      | E-mail      | contact_person@example.com |
    And I select "HR" from "Topic"
    And I fill in "Short ID" with "sst-2020"
    And I press "Propose"
    Then I should see the error message "Content with Short ID sst-2020 already exists. Please choose a different Short ID."

    # The short ID should be lowercase.
    And I fill in "Short ID" with "SSTR-2020"
    And I press "Propose"
    Then I should see the error message "This value is not valid."

    And I fill in "Short ID" with "sstr-2020"
    And I press "Propose"
    And I should see the heading "Structural Solar Traces"

    When I go to the edit form of the "Structural Solar Traces" collection
    Then the following fields should not be disabled "Short ID"

    # Publish the collection.
    When I am logged in as a moderator
    When I go to the edit form of the "Structural Solar Traces" collection
    And I press "Publish"
    Then I should see the heading "Structural Solar Traces"

    # Check that the "Short ID" field is not disabled for moderators.
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be disabled "Short ID"

    # Check that the field is not editable any more for facilitators once the
    # collection is published.
    When I am logged in as a facilitator of the "Structural Solar Traces" collection
    When I go to the edit form of the "Structural Solar Traces" collection
    Then the following fields should be disabled "Short ID"

    Then I delete the "Structural Solar Traces" collection
    And I delete the "Contact person" contact information

  Scenario: Short IDs are unique in solutions.
    Given the following collections:
      | title              | short ID | state     |
      | Scientific Studies | sst-2020 | validated |
    And solutions:
      | title                         | short ID | state     | collection         |
      | Who knows what a solution is? | wkwasi   | validated | Scientific Studies |
    And owner:
      | name                 | type    |
      | Organisation example | Company |

    When I am logged in as a facilitator of the "Scientific Studies" collection
    And I go to the homepage of the "Scientific Studies" collection
    And I click "Add solution"
    And I check "I have read and accept the legal notice and I commit to manage my solution on a regular basis."
    And I press "Yes"

    # Assert the Short ID description.
    Then I should see the following lines of text:
      | Used as the web address (URL). Example: a solution named 'European Standards for Internet Of Things' could use 'es-iot'. |
      | Cannot be changed once the solution is published.                                                                        |
      | The ID is between 4-26 characters and consists of lowercase letters, numbers and the dash symbol (-).                    |

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
    And I select "Demography" from "Topic"

    # Short ID is case insensitive.
    And I fill in "Short ID" with "wkwasi"
    And I press "Propose"
    Then I should see the error message "Content with Short ID wkwasi already exists. Please choose a different Short ID."

    # The short ID should be lowercase.
    And I fill in "Short ID" with "SST-2020"
    And I press "Propose"
    Then I should see the error message "This value is not valid."

    # The short ID is only unique among solutions, it is OK to reuse an existing
    # short ID from a collection.
    And I fill in "Short ID" with "sst-2020"
    And I press "Propose"
    Then I should see the heading "I know what a solution is"

    When I go to the edit form of the "I know what a solution is" solution
    Then the following fields should not be disabled "Short ID"

    # Publish the solution.
    When I am logged in as a moderator
    When I go to the edit form of the "I know what a solution is" solution
    And I press "Publish"
    Then I should see the heading "I know what a solution is"

    # Check that the "Short ID" field is not disabled for moderators.
    When I click "Edit" in the "Entity actions" region
    Then the following fields should not be disabled "Short ID"

    # Check that the field is not editable for facilitators.
    When I am logged in as a facilitator of the "I know what a solution is" solution
    When I go to the edit form of the "I know what a solution is" solution
    Then the following fields should be disabled "Short ID"

    Then I delete the "I know what a solution is" solution
    And I delete the "John Smith" contact information
