@api @group-e
Feature:
  As a user of the website
  When I go to the overview page of an EIRA term
  I want to be able to see if a term is deprecated.

  Scenario: Show deprecated terms without replacement.
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    Then I should see the heading "Convert an RDF entity ID"

    When I fill in "RDF entity ID or a URL" with "http://data.europa.eu/dr8/ConfigurationManagement"
    And I press "Go!"
    Then I should see the heading "Configuration Management"
    And I should see the warning message "This building block is deprecated, and should not be used in new development."
    But I should not see the warning message containing "Consider using"
    # Ensure caching.
    When I reload the page
    Then I should see the warning message "This building block is deprecated, and should not be used in new development."
    But I should not see the warning message containing "Consider using"

  Scenario: Show deprecated terms with a replacement.
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    And I fill in "RDF entity ID or a URL" with "http://data.europa.eu/dr8/ReferenceData"
    And I press "Go!"
    Then I should see the heading "Reference Data"
    And I should see the warning message "This building block is deprecated, and should not be used in new development. Consider using Data (http://data.europa.eu/dr8/Data) instead."
    # Ensure caching.
    When I reload the page
    Then I should see the warning message "This building block is deprecated, and should not be used in new development. Consider using Data (http://data.europa.eu/dr8/Data) instead."
