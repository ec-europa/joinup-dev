@api @group-e
Feature:
  As a user of the website
  When I go to the overview page of an EIRA term
  I want to be able to view information on the term.

  Scenario: Show related terms on the overview page.
    Given I am an anonymous user
    When I go to the "Public Service Provider" term page
    Then I should see the heading "Public Service Provider"
    And I should see the following links:
      | Citizen                              |
      | Public Service Consumer              |
      | Public Service Delivery Agent        |
      | Service Delivery Model               |
      | Organisational Agreement             |
      | Semantic Agreement                   |
      | Interoperable Digital Public Service |
      | Technical Interoperability Agreement |
