@api
Feature:
  As a moderator
  When I have in my possession an RDF entity ID or URL
  I want to be able to directly navigate to the content

  Scenario: Convert a URL of a taxonomy term.
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    Then I should see the heading "Convert an RDF entity ID"

    # Deliberately add a blank space in the beginning and the end of the URI to ensure trimming.
    When I fill in "RDF entity ID or a URL" with " http://data.europa.eu/dr8/PublicServiceProvider "
    And I press "Go!"
    Then I should see the heading "Public Service Provider"

  Scenario Outline: Convert a URL of an entity.
    Given collections:
      | uri   | title     | state     |
      | <uri> | <heading> | validated |

    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    Then I should see the heading "Convert an RDF entity ID"

    # Deliberately add a blank space in the beginning and the end of the URI to ensure trimming.
    When I fill in "RDF entity ID or a URL" with " <uri> "
    And I press "Go!"
    Then I should see the heading "<heading>"

    Examples:
      | uri                            | heading                                   |
      | http://test.com/example/simple | Rdf ID Converter Collection simple        |
      | http://test.com/example/       | Rdf ID Converter Collection slash         |
      | http://test.com/example#       | Rdf ID Converter Collection hash pound    |
      | http://test.com/example?       | Rdf ID Converter Collection question mark |
