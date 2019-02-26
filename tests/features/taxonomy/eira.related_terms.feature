@api
Feature:
  As a user of the website
  When I go to the overview page of an EIRA term
  I want to be able to view information on the term.

  Scenario: Show related terms on the overview page.
    When I am logged in as a moderator
    And I am on the homepage
    And I click "RDF ID converter"
    Then I should see the heading "Convert an RDF entity ID"

    When I fill in "RDF entity ID or a URL" with "http://data.europa.eu/dr8/PublicServiceProvider"
    And I press "Go!"
    Then I should see the heading "Public Service Provider"
    And I should see the following links:
      | Citizen                                   |
      | Public Service                            |
      | Public Service Consumer                   |
      | Public Service Delivery Agent             |
      | Service Delivery Model                    |
      | Public Service Agent                      |
      | Technical Interoperability Agreement      |
      | Organisational Interoperability Agreement |
      | Semantic Interoperability Agreement       |
