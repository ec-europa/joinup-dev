@api @terms @group-d
Feature: Collection content
  As a user of the website
  I want to access the content of a collection
  So that I can find the information I'm looking for.

  Background:
    Given the following collections:
      | title                     | state     |
      | Turin Egyptian Collection | validated |
    And the following solution:
      | title      | Tomb Of Unknown Restoration |
      | collection | Turin Egyptian Collection   |
      | state      | validated                   |
    And discussion content:
      | title                                 | body                                                                    | state     | collection                |
      | Bigger than Egyptian Museum of Cairo? | <p><a href="#link">Link to the museum</a> web<strong>site</strong>.</p> | validated | Turin Egyptian Collection |
    And document content:
      | title           | body                                             | state     | collection                |
      | Upper Floor Map | <p>A sample <a href="#link">map</a> example.</p> | validated | Turin Egyptian Collection |
    And event content:
      | title                                     | state     | collection                |
      | Opening of the Hellenistic Period Section | validated | Turin Egyptian Collection |
    And news content:
      | title                          | body                                                           | state     | collection                |
      | Turin Egyptian Museum Reopened | <p>After <em>more than</em> <a href="#link">two years</a>.</p> | validated | Turin Egyptian Collection |
    And video content:
      | title                                  | state     | collection                |
      | Watch the mummy conservation technique | validated | Turin Egyptian Collection |

  Scenario: Publishing new solutions should result in counters being properly updated.
    Given owner:
      | name             | type                  |
      | Particle sweeper | Private Individual(s) |

    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4436
    Given I am logged in as a moderator
    And I go to the homepage of the "Turin Egyptian Collection" collection

    # The solution counters do not include the unpublished solutions.
    Then I should see the link "Solution (1)"
    And I see the text "1 Solution" in the "Header" region

    When I click "Add solution" in the plus button menu
    And I check "I have read and accept the legal notice and I commit to manage my solution on a regular basis."
    And I press "Yes"
    And I fill in the following:
      | Title                 | Solution from draft to validated                                    |
      | Description           | Testing that publishing a solution, updates the collection content. |
      | Geographical coverage | Switzerland                                                         |
      | Name                  | Costas Papazoglou                                                   |
      | E-mail address        | CostasPapazoglou@example.com                                        |
    And I select "Data gathering, data processing" from "Topic"
    And I select "Logging Service" from "Solution type"
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
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4436
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

  @clearStaticCache
  Scenario: Content type facet labels should show the plural form when multiple results are available.
    Given I go to the homepage of the "Turin Egyptian Collection" collection
    Then the "Discussion" content tab is displayed
    And the "Document" content tab is displayed
    And the "Event" content tab is displayed
    And I should see the link "News (1)"
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
    And video content:
      | title                              | state     | collection                |
      | Understand the restoration process | validated | Turin Egyptian Collection |

    Given I reload the page
    Then the "Discussions" content tab is displayed
    And the "Documents" content tab is displayed
    And the "Events" content tab is displayed
    And I should see the link "News (2)"
    And I should see the link "Solutions (2)"
    And I should see the link "Videos (2)"

  Scenario: Links and markup should be stripped from tiles summary.
    Given I go to the homepage of the "Turin Egyptian Collection" collection
    # Check the discussion tile.
    Then I should see the "Bigger than Egyptian Museum of Cairo?" tile
    # Check into the HTML so that we assert that actually the HTML has been stripped.
    And the page should contain the html text "Link to the museum website."
    And I should not see the link "Link to the museum"
    # Check the document tile.
    And I should see the "Upper Floor Map" tile
    And the page should contain the html text "A sample map example."
    And I should not see the link "map"
    # Check the news tile.
    And I should see the "Turin Egyptian Museum Reopened" tile
