@api @group-b
Feature: Add community content
  In order to introduce my wisdom in my collections
  As a member of a collection
  I need to be able to add community content

  Scenario Outline: Advanced content and group administration should not be accessible for group members
    Given the following collection:
      | title | The night shift |
      | state | validated       |

    When I am logged in as a "<member type>" of the "The night shift" collection
    And I go to the homepage of the "The night shift" collection
    And I click "Add <content type>"
    Then I should see the heading "Add <content type>"
    But I should not see the following lines of text:
      | Authored by                  |
      | Authored on                  |
      | Create new revision          |
      | Generate automatic URL alias |
      | Groups audience              |
      | Other groups                 |
      | Promoted to front page       |
      | Revision information         |
      | Revision log message         |
      | Sticky at top of lists       |

    Examples:
      | member type | content type |
      | facilitator | discussion   |
      | facilitator | document     |
      | facilitator | event        |
      | facilitator | news         |
      | member      | discussion   |
      | member      | document     |
      | member      | event        |
      | member      | news         |

  Scenario Outline: Advanced content and group administration should not be accessible for moderators
    Given the following collection:
      | title | The night shift |
      | state | validated       |

    When I am logged in as a "moderator"
    And I go to the homepage of the "The night shift" collection
    And I click "Add <content type>"
    Then I should see the heading "Add <content type>"
    And the following fields should be present "Authored by"
    But I should not see the following lines of text:
      | Authored on                  |
      | Create new revision          |
      | Generate automatic URL alias |
      | Groups audience              |
      | Other groups                 |
      | Promoted to front page       |
      | Revision information         |
      | Revision log message         |
      | Sticky at top of lists       |

    Examples:
      | content type |
      | discussion   |
      | document     |
      | event        |
      | news         |

  Scenario Outline: Publishing community content for the first time sets the publication date
    Given users:
      | Username  | E-mail                     | First name | Family name    | Roles     |
      | Publisher | publisher-example@test.com | Publisher  | Georgakopoulos | moderator |
    And the following collection:
      | title | The afternoon shift |
      | state | validated           |
    And discussion content:
      | title             | content         | author    | state | collection          | created    |
      | Sample discussion | Sample content. | Publisher | draft | The afternoon shift | 01-01-2010 |
    And event content:
      | title        | body            | location          | author    | collection          | state | created    |
      | Sample event | Sample content. | Buckingham Palace | Publisher | The afternoon shift | draft | 01-01-2010 |
    And news content:
      | title       | headline    | body            | state | author    | collection          | created    |
      | Sample news | Sample news | Sample content. | draft | Publisher | The afternoon shift | 01-01-2010 |

    When I am logged in as "Publisher"
    And I go to the "Sample <content type>" <content type>
    Then the "Sample <content type>" <content type> should not have a publication date
#    And I should see the text "Published on: 01/01/2010"
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then I should see the heading "Sample <content type>"
    And the publication date of the "Sample <content type>" <content type> should not be equal to the created date

    When I click "Revisions" in the "Entity actions" region
    And I click the last "Revert" link
    And I press "Revert"
    Then the publication date of the "Sample <content type>" <content type> should be equal to the last unpublished version's

    When I go to the "Sample <content type>" <content type>
    And I click "Edit" in the "Entity actions" region
    And I press "Publish"
    Then I should see the heading "Sample <content type>"
    And the publication date of the "Sample <content type>" <content type> should be equal to the last published version's

    # The document is not tested as the creation date is not shown in the page. For documents, the document publication
    # date is the one shown and this field is exposed to the user.
    Examples:
      | content type |
      | discussion   |
      | event        |
      | news         |

  Scenario: Directly publishing community content sets the correct publication date.
    Given the following collections:
      | title        | description                  | logo     | banner     | state     |
      | CC container | Community content container. | logo.png | banner.jpg | validated |
    And I am logged in as a "facilitator" of the "CC container" collection

    # Create a published discussion.
    When I go to the homepage of the "CC container" collection
    And I click "Add discussion" in the plus button menu
    And I fill in the following:
      | Title   | Published community discussion |
      | Content | Published community discussion |
    And I press "Publish"
    Then I should see the heading "Published community discussion"
    And the publication date of the "Published community discussion" discussion should be equal to the created date

    # Create a published document.
    When I go to the homepage of the "CC container" collection
    And I click "Add document" in the plus button menu
    And I fill in the following:
      | Title       | Published community document |
      | Short title | Published community document |
    And I select "Study" from "Type"
    And I enter "Published community document." in the "Description" wysiwyg editor
    And I press "Publish"
    Then I should see the heading "Published community document"
    And the publication date of the "Published community document" document should be equal to the created date

    # Create a published event.
    When I go to the homepage of the "CC container" collection
    And I click "Add event" in the plus button menu
    Then the following field should not be present "Summary"
    And I fill in the following:
      | Title       | Published community event |
      | Short title | Published community event |
      | Description | Published community event |
    And I press "Add another item" at the "Virtual location" field
    And I fill the start date of the Date widget with "2018-08-29"
    And I fill the start time of the Date widget with "23:59:59"
    And I fill the end date of the Date widget with "2018-08-30"
    And I fill the end time of the Date widget with "12:57:00"
    And I fill in "Physical location" with "Rue Belliard 28, Brussels, Belgium"
    And I press "Publish"
    Then I should see the heading "Published community event"
    # We are not testing events as behat assigns a slightly different publication date than the creation date.
    # e.g. if the creation date is 1147483647, the publication date assigned will be 1147483645.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5679
    # And the publication date of the "Published community event" event should be equal to the created date

    # Create a published news.
    When I go to the homepage of the "CC container" collection
    And I click "Add news" in the plus button menu
    Then the following field should not be present "Summary"
    And I fill in the following:
      | Short title | Published community news |
      | Headline    | Published community news |
      | Content     | Published community news |
    And I press "Publish"
    Then I should see the heading "Published community news"
    And the publication date of the "Published community news" news should be equal to the created date
