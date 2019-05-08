@api
Feature:
  As a moderator
  When I have in my possession an RDF entity ID or URL
  I want to be able to directly navigate to the content

  Scenario: Convert a URL of a taxonomy term
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    Then I should see the heading "Convert an RDF entity ID"

    When I fill in "RDF entity ID or a URL" with "http://data.europa.eu/dr8/PublicServiceProvider"
    And I press "Go!"
    Then I should see the heading "Public Service Provider"
