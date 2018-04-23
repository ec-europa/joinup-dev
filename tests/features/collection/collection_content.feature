@api @terms
Feature: Given I am visiting the collection homepage
  I want to see the content tabs with the proper singular/plural labels.

  Background:
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

  Scenario: Test that publishing new solutions result in counters being properly updated.
    Given owner:
      | name             | type                  |
      | Particle sweeper | Private Individual(s) |

    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4436
    Given I am logged in as a moderator
    And I go to the homepage of the "Turin Egyptian Collection" collection

    # The solution counters do not include the unpublished solutions.
    Then I should see the link "Solution (1)"
    And I see the text "1 Solution" in the "Header" region

    When I click "Add solution" in the plus button menu
    And I fill in the following:
      | Title            | Solution from draft to validated                                    |
      | Description      | Testing that publishing a solution, updates the collection content. |
      | Spatial coverage | Switzerland                                                         |
      | Name             | Costas Papazoglou                                                   |
      | E-mail address   | CostasPapazoglou@example.com                                        |
    And I select "Data gathering, data processing" from "Policy domain"
    And I select "[ABB59] Logging Service" from "Solution type"
    And I press "Add existing" at the "Owner" field
    And I fill in "Owner" with "Particle sweeper"
    And I press "Add owner"
    And I press "Publish"
    Then I should see the heading "Solution from draft to validated"

    When I go to the homepage of the "Turin Egyptian Collection" collection
    # Since there are 2 solutions, the link moved in first since it is the type of content with most items.
    # When the facet tab is displayed, the text is "@count Solutions", while if it is in the "More" dropdown, it shows
    # as "Solutions (@count)".
    Then I see the text "2 Solution" in the "Header" region
    And I should see the link "2 Solutions"

    # Create a draft version of the solution to verify that counters are not affected.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4436
    When I go to the homepage of the "Solution from draft to validated" solution
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Solution from draft to validated but draft"
    And I press "Save as draft"
    Then I should see the heading "Solution from draft to validated"

    When I go to the homepage of the "Turin Egyptian Collection" collection
    Then I see the text "2 Solution" in the "Header" region
    And I should see the link "2 Solutions"
    Then I delete the "Solution from draft to validated" solution
    And I delete the "Costas Papazoglou" contact information

  Scenario: Test label variant based on the content count of each category.
    Given I go to the homepage of the "Turin Egyptian Collection" collection
    Then the "Discussion" content tab is displayed
    And the "Document" content tab is displayed
    And the "Event" content tab is displayed
    And I should see the link "News (1)"
    And I should see the link "Newsletter (1)"
    And I should see the link "Solution (1)"
    And I should see the link "Video (1)"

    Given the following solution:
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
