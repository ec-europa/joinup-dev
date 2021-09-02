@api
Feature:
  As the owner of the website
  In order for my site to be more appealing to the search engines
  I need to have basic metatags attached to the page.

  Scenario Outline: Front page metatags.
    Given I am <user type>
    When I am on the homepage
    Then the following meta tags should available in the html:
      | identifier    | value                                                                                                                                                                                                                      |
      | description   | Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme.            |
      | abstract      | Joinup offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions. |
      | og:url        | __base_url__/                                                                                                                                                                                                              |
      | og:site_name  | Joinup                                                                                                                                                                                                                     |
      | og:title      | Joinup                                                                                                                                                                                                                     |
      | og:image      | __base_url__/themes/joinup/images/logo.svg                                                                                                                                                                                 |
      | og:image:type | image/svg+xml                                                                                                                                                                                                              |
    And the HTML title of the page should be "Joinup"

    When I visit the collection overview
    Then the following meta tags should available in the html:
      | identifier    | value                                      |
      | og:url        | __base_url__/collections                   |
      | og:site_name  | Joinup                                     |
      | og:title      | Collections                                |
      | og:image      | __base_url__/themes/joinup/images/logo.svg |
      | og:image:type | image/svg+xml                              |

    Examples:
      | user type                                         |
      | an anonymous user                                 |
      | logged in as a user with the "authenticated" role |
