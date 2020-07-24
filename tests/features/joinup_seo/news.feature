@api
Feature: SEO for news articles.
  As an owner of the website
  in order for my news to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Scenario: Basic metatags are attached as JSON schema on the page.
    Given collections:
      | title                      | state     |
      | Joinup SEO news collection | validated |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Kurk       | Smith       |
    And "news" content:
      | title           | headline                    | logo     | body:summary     | body          | created                         | publication date                | changed                         | state     | author            | collection                 |
      | Joinup SEO news | Headline of Joinup SEO news | logo.png | Summary of news. | Body of news. | Sun, 01 Dec 2019 13:00:00 +0100 | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | validated | Joinup SEO author | Joinup SEO news collection |

    When I visit the "Joinup SEO news" news
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "NewsArticle" should exist in the page
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following properties:
      | property            | value                                                                   |
      | @type               | NewsArticle                                                             |
      | headline            | Headline of Joinup SEO news                                             |
      # Summary is preferred over the body of the entity.
      | description         | Summary of news.                                                        |
      | isAccessibleForFree | True                                                                    |
      | datePublished       | 2019-12-25T13:00:00+0100                                                |
      | dateModified        | 2020-01-01T13:00:00+0100                                                |
      # __base_url__ will be replaced with the base url of the website.
      | mainEntityOfPage    | __base_url__/collection/joinup-seo-news-collection/news/joinup-seo-news |
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following "image" properties:
      | property             | value       |
      | @type                | ImageObject |
      | representativeOfPage | True        |
      # The test files are assigned a random name so it is very hard to assert the real url. However, when the file is
      # found and added to the page, the dimensions are added as well. Asserting the dimensions also asserts the url in
      # a way.
      | width                | 377         |
      | height               | 139         |
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following "author" properties:
      | property | value                             |
      | @type    | Person                            |
      | @id      | __base_url__/user/__random_text__ |
      | name     | Kurk Smith                        |
      | url      | __base_url__/user/__random_text__ |
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following "publisher" properties:
      | property | value                             |
      | @type    | Person                            |
      | @id      | __base_url__/user/__random_text__ |
      | name     | Kurk Smith                        |
      | url      | __base_url__/user/__random_text__ |
    And the following meta tags should available in the html:
      | identifier             | value                                                                   |
      | description            | Summary of news.                                                        |
      | og:url                 | __base_url__/collection/joinup-seo-news-collection/news/joinup-seo-news |
      | og:site_name           | Joinup                                                                  |
      | og:title               | Joinup SEO news                                                         |
      | og:description         | Summary of news.                                                        |
      | og:image               | __base_url__/sites/default/files/__random_text__.jpg                    |
      | og:image:type          | image/jpeg                                                              |
      | og:image:width         | 377                                                                     |
      | og:image:height        | 139                                                                     |
      | article:author         | Kurk Smith                                                              |
      | article:published_time | 2019-12-25T13:00:00+0100                                                |
      | article:modified_time  | 2020-01-01T13:00:00+0100                                                |

    When I click "Keep up to date"
    Then I should see the "Joinup SEO news" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the news item
    # is also not attached when the tile is present.
    And the metatag JSON should not be attached in the page
