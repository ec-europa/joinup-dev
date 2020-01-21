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
      | name  | Sitemap secreteriat             |
      | email | sitemap.secreteriat@example.com |
    And the following collections:
      | title                        | state     |
      | Sitemap collection draft     | draft     |
      | Sitemap collection validated | validated |
    And the following solutions:
      | title                      | description                 | owner         | contact information | collection                   | state     |
      | Sitemap solution draft     | Sitemap keywords everywhere | Sitemap owner | Sitemap secreteriat | Sitemap collection validated | draft     |
      | Sitemap solution validated | Sitemap keywords everywhere | Sitemap owner | Sitemap secreteriat | Sitemap collection validated | validated |
    And the following releases:
      | title             | release number | release notes | is version of              | owner         | contact information | state     |
      | Sitemap release 1 | 1              | New release   | Sitemap solution validated | Sitemap owner | Sitemap secreteriat | validated |
      | Sitemap release 2 | 2              | Newer release | Sitemap solution validated | Sitemap owner | Sitemap secreteriat | draft     |
    And the following distributions:
      | title                | description           | parent            | access url |
      | Sitemap distribution | Some kind of download | Sitemap release 1 | test.zip   |
    And the following licences:
      | title           | description |
      | Sitemap licence | Not allowed |
    And "custom_page" content:
      | title                            | collection                   | body | logo     |
      | Sitemap custom page of draft     | Sitemap collection draft     | N/A  | logo.png |
      | Sitemap custom page of validated | Sitemap collection validated | N/A  | logo.png |
    And news content:
      | title                                    | headline     | body              | solution                   | state     |
      | Sitemap news draft                       | Sitemap news | Sitemap news body | Sitemap solution validated | draft     |
      | Sitemap news validated                   | Sitemap news | Sitemap news body | Sitemap solution validated | validated |
      | Sitemap news validated but parent is not | Sitemap news | Sitemap news body | Sitemap solution draft     | validated |
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

    But I should not see the absolute urls of the following RDF entities:
      | Sitemap collection draft |
      | Sitemap solution draft   |
      | Sitemap release 2        |
      | Sitemap distribution     |
      | Sitemap licence          |
      | Sitemap owner            |
      | Sitemap secreteriat      |
    And I should not see the absolute urls of the following content entities:
      | Sitemap custom page of draft                   |
      | Sitemap discussion draft                       |
      | Sitemap discussion validated but parent is not |
      | Sitemap document draft                         |
      | Sitemap document validated but parent is not   |
      | Sitemap event draft                            |
      | Sitemap event validated but parent is not      |
      | Sitemap news draft                             |
      | Sitemap news validated                         |
      | Sitemap news validated but parent is not       |

    When I visit "/news/sitemap.xml"
    And I should see the absolute urls of the following content entities:
      | Sitemap news validated |

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
      | Sitemap secreteriat          |
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
      | Sitemap news validated but parent is not       |
