@api
Feature: As an owner of the website
  in order for my news to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Scenario: Basic metatags are attached as json schema on the page.
    Given collections:
      | title                      | state     |
      | Joinup SEO news collection | validated |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Kurk       | Smith       |
    And "news" content:
      | title           | headline                    | body                    | state     | author            | collection                 |
      | Joinup SEO news | Headline of Joinup SEO news | Body of Joinup SEO news | validated | Joinup SEO author | Joinup SEO news collection |

    When I visit the "Joinup SEO news" news
    Then the metatag json should be attached in the page
    And 1 metatag graph of type "NewsArticle" should exist in the page
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following properties:
      | property         | value                                                                 |
      | @type            | NewsArticle                                                           |
      | headline         | Headline of Joinup SEO news                                           |
      | description      | Body of Joinup SEO news                                               |
      # $base_url$ will be replaced with the base url of the website.
      | mainEntityOfPage | $base_url$/collection/joinup-seo-news-collection/news/joinup-seo-news |
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following "author" properties:
      | property | value      |
      | @type    | Person     |
      | name     | Kurk Smith |
    And the metatag graph of the item with "name" "Joinup SEO news" should have the following "publisher" properties:
      | property | value        |
      | @type    | Organization |
      | name     | Joinup       |
      | url      | $base_url$/  |

    When I click "Keep up to date"
    Then I should see the "Joinup SEO news" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the news item
    # is also not attached when the tile is present.
    And the metatag json should not be attached in the page
