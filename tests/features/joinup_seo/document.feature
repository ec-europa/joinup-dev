@api @group-c
Feature: SEO for document content.
  As an owner of the website
  in order for my documents to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Background:
    Given collections:
      | title                          | state     |
      | Joinup SEO document collection | validated |
    And licence:
      | uri   | https://example.com/license1 |
      | title | Some license                 |
      | type  | Public domain                |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Scrapper   | Jedi        |

  Scenario: Basic metatags are attached as JSON schema on the page.
    Given document content:
      | title        | author            | document type | document publication date       | changed                         | keywords         | short title | file type | file     | body               | licence      | state     | collection                     |
      | SEO document | Joinup SEO author | document      | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | key1, key2, key3 | SEO         | upload    | test.zip | Document test1.zip | Some license | validated | Joinup SEO document collection |

    When I visit the "SEO document" document
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "DigitalDocument" should exist in the page
    And the metatag graph of the item with "name" "SEO document" should have the following properties:
      | property            | value                                                    |
      | @type               | DigitalDocument                                          |
      | headline            | SEO document                                             |
      | name                | SEO document                                             |
      | license             | https://example.com/license1                             |
      | description         | Document test1.zip                                       |
      | datePublished       | 2019-12-25T__timezone__:00:00+0100                       |
      | isAccessibleForFree | True                                                     |
      | dateModified        | 2020-01-01T__timezone__:00:00+0100                       |
      | mainEntityOfPage    | __base_url__/sites/default/files/test__random_text__.zip |
    # Adding numerical property values is turning the "about" property into an array comparison.
    And the metatag graph of the item with "name" "SEO document" should have the following "about" properties:
      | property | value |
      | 0        | key1  |
      | 1        | key2  |
      | 2        | key3  |
    And the metatag graph of the item with "name" "SEO document" should have the following "associatedMedia" properties:
      | property      | value                                                    |
      | @type         | MediaObject                                              |
      # __random_text__ can be any string that is appointed by the system and we
      # cannot predict. In this case it is the random file name suffix before the file extension.
      | @id           | __base_url__/sites/default/files/test__random_text__.zip |
      | name          | test.zip                                                 |
      | url           | __base_url__/sites/default/files/test__random_text__.zip |
      | datePublished | 2019-12-25T__timezone__:00:00+0100                       |
    And the metatag graph of the item with "name" "SEO document" should have the following "author" properties:
      | property | value                             |
      | @type    | Person                            |
      # The user id is only a number but we can be quite certain that this will be a url to the user since the
      # __random_text__ does not include a / character.
      | @id      | __base_url__/user/__random_text__ |
      | name     | Scrapper Jedi                     |
      | url      | __base_url__/user/__random_text__ |
    And the following meta tags should available in the html:
      | identifier     | value                                                                        |
      | description    | Document test1.zip                                                           |
      | og:url         | __base_url__/collection/joinup-seo-document-collection/document/seo-document |
      | og:site_name   | Joinup                                                                       |
      | og:title       | SEO document                                                                 |
      | og:description | Document test1.zip                                                           |

    When I click "Keep up to date"
    Then I should see the "SEO document" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the
    # news item is also not attached when the tile is present.
    And the metatag JSON should not be attached in the page

  Scenario: Metatags for remote URL in documents.
    Given document content:
      | title        | author            | document publication date       | changed                         | file type | file                                       | body               | state     | collection                     |
      | SEO document | Joinup SEO author | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | remote    | http://example.com/some-file-url.extension | Remote url example | validated | Joinup SEO document collection |

    When I visit the "SEO document" document
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "DigitalDocument" should exist in the page
    And the metatag graph of the item with "name" "SEO document" should have the following properties:
      | property            | value                                      |
      | @type               | DigitalDocument                            |
      | headline            | SEO document                               |
      | name                | SEO document                               |
      | description         | Remote url example                         |
      | datePublished       | 2019-12-25T__timezone__:00:00+0100         |
      | isAccessibleForFree | True                                       |
      | dateModified        | 2020-01-01T__timezone__:00:00+0100         |
      | mainEntityOfPage    | http://example.com/some-file-url.extension |
    And the metatag graph of the item with "name" "SEO document" should have the following "associatedMedia" properties:
      | property      | value                                      |
      | @type         | MediaObject                                |
      | @id           | http://example.com/some-file-url.extension |
      | name          | some-file-url.extension                    |
      | url           | http://example.com/some-file-url.extension |
      | datePublished | 2019-12-25T__timezone__:00:00+0100         |
