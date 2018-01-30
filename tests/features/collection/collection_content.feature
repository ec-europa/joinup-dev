@api
Feature: Given I am visiting the collection homepage
  I want to see the content tabs with the proper singular/plural labels.

  Scenario: Test label variant based on the content count of each category.

    Given the following collection:
      | title | Turin Egyptian Collection |
      | state | validated                 |
    And the following solution:
      | title      | Tomb Of Unknown Restoration |
      | collection | Turin Egyptian Collection   |
      | state      | validated                   |
    And discussion content:
      | title                                 | state     | collection                |
      | Bigger than Egyptian Museum of Cairo? | validated | Turin Egyptian Collection |
    And document content:
      | title           | state     | collection                |
      | Upper Floor Map | validated | Turin Egyptian Collection |
    And event content:
      | title                                     | state     | collection                |
      | Opening of the Hellenistic Period Section | validated | Turin Egyptian Collection |
    And news content:
      | title                          | state     | collection                |
      | Turin Egyptian Museum Reopened | validated | Turin Egyptian Collection |
    And newsletter content:
      | title                                                | state     | collection                |
      | Stay informed about this year events and exhibitions | validated | Turin Egyptian Collection |
    And video content:
      | title                                  | state     | collection                |
      | Watch the mummy conservation technique | validated | Turin Egyptian Collection |

    Given I go to the homepage of the "Turin Egyptian Collection" collection

    Then the "Discussion" content tab is displayed
    And the "Document" content tab is displayed
    And the "Event" content tab is displayed
    And I should see the link "News (1)"
    And I should see the link "Newsletter (1)"
    And I should see the link "Solution (1)"
    And I should see the link "Video (1)"

    And the following solution:
      | title      | Protecting Artifacts      |
      | collection | Turin Egyptian Collection |
      | state      | validated                 |
    And discussion content:
      | title                              | state     | collection                |
      | Is the entrance free for children? | validated | Turin Egyptian Collection |
    And document content:
      | title                   | state     | collection                |
      | Fire Safety Regulations | validated | Turin Egyptian Collection |
    And event content:
      | title                        | state     | collection                |
      | Cleopatra Jewelry Exhibition | validated | Turin Egyptian Collection |
    And news content:
      | title                                  | state     | collection                |
      | Museum Temporary Closed Next Wednesday | validated | Turin Egyptian Collection |
    And newsletter content:
      | title                                  | state     | collection                |
      | New exhibition opening at ground floor | validated | Turin Egyptian Collection |
    And video content:
      | title                              | state     | collection                |
      | Understand the restoration process | validated | Turin Egyptian Collection |

    # @todo Remove this line as part of ISAICP-4280.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4280
    Given the cache has been cleared

    Given I reload the page

    Then the "Discussions" content tab is displayed
    And the "Documents" content tab is displayed
    And the "Events" content tab is displayed
    And I should see the link "News (2)"
    And I should see the link "Newsletters (2)"
    And I should see the link "Solutions (2)"
    And I should see the link "Videos (2)"
