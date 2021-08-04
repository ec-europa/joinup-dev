@api
Feature:
  As a user of the website
  When I go to the overview page of an EIRA term
  I want to be able to view information on the term.

  Scenario: Show related terms on the overview page.
    Given I am an anonymous user
    When I go to the "[ABB174] Public Service Provider" term page
    Then I should see the heading "[ABB174] Public Service Provider"
    And I should not see the following links:
      | [ABB8] Citizen                                |
      | [ABB5] Public Service Consumer                |
      | [ABB173] Public Service Delivery Agent        |
      | [ABB15] Service Delivery Model                |
      | [ABB374] Semantic Agreement                   |
      | [ABB234] Technical Interoperability Agreement |
