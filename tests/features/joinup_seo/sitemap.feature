@api
Feature:
  As the owner of the site
  In order to promote content to the search engines properly
  I need to have the proper sitemaps available.

  Scenario: Basic sitemap link availability.
    Given the following owner:
      | name          | type    |
      | Sitemap owner | Company |
    And the following contact:
      | name  | Sitemap secretariat             |
      | email | sitemap.secretariat@example.com |
    And the following collections:
      | title                        | state     |
      | Sitemap collection draft     | draft     |
      | Sitemap collection validated | validated |
    And the following solutions:
      | title                      | description                 | owner         | contact information | collection                   | state     |
      | Sitemap solution draft     | Sitemap keywords everywhere | Sitemap owner | Sitemap secretariat | Sitemap collection validated | draft     |
      | Sitemap solution validated | Sitemap keywords everywhere | Sitemap owner | Sitemap secretariat | Sitemap collection validated | validated |
    And the following releases:
      | title             | release number | release notes | is version of              | owner         | contact information | state     |
      | Sitemap release 1 | 1              | New release   | Sitemap solution validated | Sitemap owner | Sitemap secretariat | validated |
      | Sitemap release 2 | 2              | Newer release | Sitemap solution validated | Sitemap owner | Sitemap secretariat | draft     |
    And the following distributions:
      | title                | description           | parent            | access url |
      | Sitemap distribution | Some kind of download | Sitemap release 1 | test.zip   |
    And the following licences:
      | title           | description |
      | Sitemap licence | Not allowed |
    And "custom_page" content:
      | title                                           | collection                   | body | logo     | langcode |
      | Sitemap custom page of draft                    | Sitemap collection draft     | N/A  | logo.png | en       |
      | Sitemap custom page of validated                | Sitemap collection validated | N/A  | logo.png | en       |
      | Sitemap custom page of validated but in Spanish | Sitemap collection validated | N/A  | logo.png | es       |
    And news content:
      | title                                    | headline     | body              | solution                   | state     | publication date                |
      | Sitemap news draft                       | Sitemap news | Sitemap news body | Sitemap solution validated | draft     |                                 |
      | Sitemap news validated and recent        | Sitemap news | Sitemap news body | Sitemap solution validated | validated | 1 day ago                       |
      | Sitemap news validated but old           | Sitemap news | Sitemap news body | Sitemap solution validated | validated | 2 months ago                    |
      | Sitemap news validated but parent is not | Sitemap news | Sitemap news body | Sitemap solution draft     | validated | Sun, 01 Dec 2019 13:00:00 +0100 |
    And event content:
      | title                                     | short title             | body                      | agenda        | location   | organisation        | scope         | solution                   | state     |
      | Sitemap event draft                       | Sitemap event draft     | It will include fireworks | Event agenda. | Some place | European Commission | International | Sitemap solution validated | draft     |
      | Sitemap event validated                   | Sitemap event validated | It will include fireworks | Event agenda. | Some place | European Commission | International | Sitemap solution validated | validated |
      | Sitemap event validated but parent is not | Sitemap event validated | It will include fireworks | Event agenda. | Some place | European Commission | International | Sitemap solution draft     | validated |
    And document content:
      | title                                        | document type | short title                | body         | solution                   | state     |
      | Sitemap document draft                       | Document      | Sitemap document draft     | Read more... | Sitemap solution validated | draft     |
      | Sitemap document validated                   | Document      | Sitemap document validated | Read more... | Sitemap solution validated | validated |
      | Sitemap document validated but parent is not | Document      | Sitemap document validated | Read more... | Sitemap solution draft     | validated |
    And discussion content:
      | title                                          | body                  | solution                   | state     |
      | Sitemap discussion draft                       | Wanna discuss this??? | Sitemap solution validated | draft     |
      | Sitemap discussion validated                   | Wanna discuss this??? | Sitemap solution validated | validated |
      | Sitemap discussion validated but parent is not | Wanna discuss this??? | Sitemap solution draft     | validated |

    Given I run cron
    And I visit "/sitemap.xml"
    Then I should see the absolute urls of the following RDF entities:
      | Sitemap collection validated |
      | Sitemap solution validated   |
      | Sitemap release 1            |
    And I should see the absolute urls of the following content entities:
      | Sitemap custom page of validated |
      | Sitemap discussion validated     |
      | Sitemap document validated       |
      | Sitemap event validated          |
      | Sitemap news validated but old   |

    But I should not see the absolute urls of the following RDF entities:
      | Sitemap collection draft |
      | Sitemap solution draft   |
      | Sitemap release 2        |
      | Sitemap distribution     |
      | Sitemap licence          |
      | Sitemap owner            |
      | Sitemap secretariat      |
    And I should not see the absolute urls of the following content entities:
      | Sitemap custom page of draft                    |
      | Sitemap custom page of validated but in Spanish |
      | Sitemap discussion draft                        |
      | Sitemap discussion validated but parent is not  |
      | Sitemap document draft                          |
      | Sitemap document validated but parent is not    |
      | Sitemap event draft                             |
      | Sitemap event validated but parent is not       |
      | Sitemap news draft                              |
      | Sitemap news validated and recent               |
      | Sitemap news validated but parent is not        |
    # The following piece of xhtml is used to declare an alternative language. If it exists in the page it means that
    # another language is included in the output.
    And the response should not contain "<xhtml:link rel=\"alternate\" hreflang="

    # Check the dedicated sitemap for news articles that only contains hot news
    # topics published in the last 2 days.
    When I visit "/news/sitemap.xml"
    And I should see the absolute urls of the following content entities:
      | Sitemap news validated and recent |

    But I should not see the absolute urls of the following RDF entities:
      | Sitemap collection draft     |
      | Sitemap collection validated |
      | Sitemap distribution         |
      | Sitemap solution draft       |
      | Sitemap solution validated   |
      | Sitemap release 1            |
      | Sitemap release 2            |
      | Sitemap licence              |
      | Sitemap owner                |
      | Sitemap secretariat          |
    And I should not see the absolute urls of the following content entities:
      | Sitemap custom page of draft                   |
      | Sitemap discussion draft                       |
      | Sitemap discussion validated                   |
      | Sitemap discussion validated but parent is not |
      | Sitemap document draft                         |
      | Sitemap document validated                     |
      | Sitemap document validated but parent is not   |
      | Sitemap event draft                            |
      | Sitemap event validated                        |
      | Sitemap event validated but parent is not      |
      | Sitemap news draft                             |
      | Sitemap news validated but old                 |
      | Sitemap news validated but parent is not       |

    # Simulate that 2 days have passed since last time the sitemaps have been created.
    When the publication date of the "Sitemap news validated and recent" news content is changed to "3 days ago"
    And I run cron
    # Assert that the "Sitemap news validated and recent" news article is no
    # longer considered as a hot topic and has moved to the standard sitemap.
    And I visit "/sitemap.xml"
    Then I should see the absolute urls of the following RDF entities:
      | Sitemap collection validated |
      | Sitemap solution validated   |
      | Sitemap release 1            |
    And I should see the absolute urls of the following content entities:
      | Sitemap custom page of validated  |
      | Sitemap discussion validated      |
      | Sitemap document validated        |
      | Sitemap event validated           |
      | Sitemap news validated and recent |
      | Sitemap news validated but old    |

    But I should not see the absolute urls of the following RDF entities:
      | Sitemap collection draft |
      | Sitemap solution draft   |
      | Sitemap release 2        |
      | Sitemap distribution     |
      | Sitemap licence          |
      | Sitemap owner            |
      | Sitemap secretariat      |
    And I should not see the absolute urls of the following content entities:
      | Sitemap custom page of draft                   |
      | Sitemap discussion draft                       |
      | Sitemap discussion validated but parent is not |
      | Sitemap document draft                         |
      | Sitemap document validated but parent is not   |
      | Sitemap event draft                            |
      | Sitemap event validated but parent is not      |
      | Sitemap news draft                             |
      | Sitemap news validated but parent is not       |

    # The hot news article is now removed from the news sitemap, and the page
    # should no longer be present since it doesn't contain any more entries.
    When I go to "/news/sitemap.xml"
    Then the response status code should be 404
