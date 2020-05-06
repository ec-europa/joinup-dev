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
      | abstract      | EIC Covid19 is funded by the European Union via Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme. |
      | description   | EIC Covid19 is a collaborative platform created by the European Commission as a follow up mechanism to the Covid19 challenges presented at the EUvsVIRUS Hackathon, but also able to include other challenges in the future.   |
      | og:url        | __base_url__/                                                                                                                                                                                                              |
      | og:site_name  | COVID-19 Challenge                                                                                                                                                                                                                     |
      | og:title      | COVID-19 Challenge                                                                                                                                                                                                                     |
      | og:image      | __base_url__/themes/joinup/images/logo.svg                                                                                                                                                                                 |
      | og:image:type | image/svg+xml                                                                                                                                                                                                              |
    And the HTML title of the page should be "COVID-19 Challenge"

    When I click "Challenges"
    Then the following meta tags should available in the html:
      | identifier    | value                                      |
      | og:url        | __base_url__/challenges                   |
      | og:site_name  | COVID-19 Challenge                                     |
      | og:title      | Challenges                                |
      | og:image      | __base_url__/themes/joinup/images/logo.svg |
      | og:image:type | image/svg+xml                              |

    Examples:
      | user type                                         |
      | an anonymous user                                 |
      | logged in as a user with the "authenticated" role |
