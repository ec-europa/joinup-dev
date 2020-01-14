@api
Feature:
  As the owner of the website
  In order for my site to be more appealing to the search engines
  I need to have basic metatags attached to the page.

  Scenario Outline: Front page metatags.
    Given I am <user type>
    When I am on the homepage
    Then the "description" metatag should be set to "Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme."
    And the "abstract" metatag should be set to "It offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions."
    And the HTML title of the page should be "Joinup"

    Examples:
      | user type                                         |
      | an anonymous user                                 |
      | logged in as a user with the "authenticated" role |
