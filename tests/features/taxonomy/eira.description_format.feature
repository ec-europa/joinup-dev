@api @group-e
Feature:
  In order to better read the eira descriptions
  As a user of the website
  I need the description to be shown in a nice way.

  Scenario: Apply a formatter to the description field that allows the description to be properly formatted.
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    And I fill in "RDF entity ID or a URL" with "http://data.europa.eu/dr8/PublicPolicy"
    And I press "Go!"
    Then I should see the heading "Public Policy"
    # The <br /> tags are not part of the original description.
    And the response should contain "<p>DESCRIPTION:<br />"
    And the response should contain "<p>INTEROPERABILITY SALIENCY:<br />"
