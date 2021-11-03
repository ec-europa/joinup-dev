@api @group-c
Feature: SEO for discussion forum posts.
  As an owner of the website
  in order for my discussions to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Scenario: Basic metatags are attached as JSON schema on the page.
    Given collections:
      | title                            | state     |
      | Joinup SEO discussion collection | validated |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Kindle     | eReader     |
    And "discussion" content:
      | title                           | publication date                | changed                         | content                                                      | author            | attachments         | keywords                       | state     | collection                       |
      | Discussions are now forum posts | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | This discussion is to ensure that SEO tags are set properly. | Joinup SEO author | test.zip, test1.zip | seo, tags, metatag, schema.org | validated | Joinup SEO discussion collection |

    When I visit the "Discussions are now forum posts" discussion
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "DiscussionForumPosting" should exist in the page
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following properties:
      | property            | value                                                                                               |
      | @type               | DiscussionForumPosting                                                                              |
      | headline            | Discussions are now forum posts                                                                     |
      | name                | Discussions are now forum posts                                                                     |
      | description         | This discussion is to ensure that SEO tags are set properly.                                        |
      | datePublished       | 2019-12-25T13:00:00+0100                                                                            |
      | isAccessibleForFree | True                                                                                                |
      | dateModified        | 2020-01-01T13:00:00+0100                                                                            |
      | mainEntityOfPage    | __base_url__/collection/joinup-seo-discussion-collection/discussion/discussions-are-now-forum-posts |
    # Adding numerical property values is turning the "about" property into an array comparison.
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "about" properties:
      | property | value      |
      | 0        | seo        |
      | 1        | tags       |
      | 2        | metatag    |
      | 3        | schema.org |
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "image" properties:
      | property | value                                                                                                                                           |
      | @type    | ImageObject                                                                                                                                     |
      # Discussions don't have an image field but an image is required by google. Add the Joinup logo as the image of
      # all discussions.
      | url      | https://joinup.ec.europa.eu/sites/default/files/styles/logo/public/collection/logo/2019-04/190404-logo-JOINUP-blue-2.png |
    # The index is the delta in the field attachment, with 0 meaning the first of the values.
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "sharedContent" properties in index 0:
      | property | value                                                    |
      | @type    | MediaObject                                              |
      # __random_text__ can be any string that is appointed by the system and we
      # cannot predict. In this case it is the random file name suffix before the file extension.
      | @id      | __base_url__/sites/default/files/test__random_text__.zip |
      | name     | test.zip                                                 |
      | url      | __base_url__/sites/default/files/test__random_text__.zip |
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "sharedContent" properties in index 1:
      | property | value                                                    |
      | @type    | MediaObject                                              |
      # __random_text__ can be any string that is appointed by the system and we
      # cannot predict. In this case it is the random file name suffix before the file extension.
      | @id      | __base_url__/sites/default/files/test__random_text__.zip |
      | name     | test1.zip                                                |
      | url      | __base_url__/sites/default/files/test__random_text__.zip |
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "author" properties:
      | property | value                             |
      | @type    | Person                            |
      # The user id is only a number but we can be quite certain that this will be a url to the user since the
      # __random_text__ does not include a / character.
      | @id      | __base_url__/user/__random_text__ |
      | name     | Kindle eReader                    |
      | url      | __base_url__/user/__random_text__ |
    And the metatag graph of the item with "name" "Discussions are now forum posts" should have the following "publisher" properties:
      | property | value                             |
      | @type    | Person                            |
      # The user id is only a number but we can be quite certain that this will be a url to the user since the
      # __random_text__ does not include a / character.
      | @id      | __base_url__/user/__random_text__ |
      | name     | Kindle eReader                    |
      | url      | __base_url__/user/__random_text__ |
    And the following meta tags should available in the html:
      | identifier     | value                                                                                               |
      | description    | This discussion is to ensure that SEO tags are set properly.                                        |
      | og:url         | __base_url__/collection/joinup-seo-discussion-collection/discussion/discussions-are-now-forum-posts |
      | og:site_name   | Joinup                                                                                              |
      | og:title       | Discussions are now forum posts                                                                     |
      | og:description | This discussion is to ensure that SEO tags are set properly.                                        |

    When I click "Keep up to date"
    Then I should see the "Discussions are now forum posts" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the
    # news item is also not attached when the tile is present.
    And the metatag JSON should not be attached in the page
