@api @terms @group-a
Feature: Global search
  In order to effectively find the content I am looking for
  As someone who is looking for information from a certain time period
  I need to be able to manipulate a date filter

  Scenario: Filter content by publication date
    # Collections (and all other RDF content) do not store the publication date
    # at this time. As a fallback the creation date is used in the date filter.
    Given the following collection:
      | title         | Bento            |
      | state         | validated        |
      | creation date | 2021-02-04T17:55 |
    And the following solutions:
      | title           | creation date    | state     | collection |
      | Stackable boxes | 2021-11-10T03:09 | validated | Bento      |
    And news content:
      | title                 | creation date | publication date | state     | collection |
      | Flower-shaped carrots | 2020-02-06    | 2020-11-03T12:41 | validated | Bento      |
    And event content:
      | title              | creation date | publication date | state     | collection |
      | Sesame harvest day | 2001-01-06    | 2014-06-06T22:46 | validated | Bento      |
    # A document has an editable publication date that takes precedence over the
    # actual publication date on Joinup. This is intended for official documents
    # for which the publication date on their canonical source is important.
    And document content:
      | title | creation date | publication date | document publication date | state     | collection |
      | Sake  | 2017-11-29    | 2017-11-30T12:45 | 2009-06-18T10:50          | validated | Bento      |
      | Soba  | 2019-07-12    | 2019-07-13T13:55 |                           | validated | Bento      |
    And discussion content:
      | title        | creation date | publication date | state     | collection |
      | More garlic? | 2012-01-09    | 2013-04-11T12:35 | validated | Bento      |
    And releases:
      | title    | creation date    | release number | release notes | state     | is version of   |
      | Mentsuyu | 2015-11-03T16:35 | 1              | First notes.  | validated | Stackable boxes |
    And custom_page content:
      | title    | creation date | publication date | status    | collection |
      | Yakiniku | 2013-05-30    | 2013-11-25T10:05 | published | Bento      |
    And video content:
      | title            | creation date | publication date | state     | collection |
      | Licence to grill | 2020-08-04    | 2021-09-10T14:00 | validated | Bento      |

    When I visit the search page
    And I fill in "Published between" with "2020-01-01" in the "Left sidebar" region
    # Todo: ensure the search works when only entering only the first field.
    # Ref. https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6362
    And I fill in "And" with "2099-12-31" in the "Left sidebar" region
    And I press "Apply" in the "Left sidebar" region
    Then the page should show the tiles "Bento, Stackable boxes, Flower-shaped carrots, Licence to grill"

    # Todo: ensure the search works when only entering only the second field.
    # Ref. https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6362
    When I fill in "Published between" with "1900-01-01" in the "Left sidebar" region
    And I fill in "And" with "2009-08-01" in the "Left sidebar" region
    And I press "Apply" in the "Left sidebar" region
    Then the page should show the tiles "Sake"

    When I fill in "Published between" with "2010-01-01" in the "Left sidebar" region
    And I fill in "And" with "2014-07-20" in the "Left sidebar" region
    And I press "Apply" in the "Left sidebar" region
    Then the page should show the tiles "Sesame harvest day, More garlic?, Yakiniku"

    When I fill in "Published between" with "2015-02-03" in the "Left sidebar" region
    And I fill in "And" with "2019-09-30" in the "Left sidebar" region
    And I press "Apply" in the "Left sidebar" region
    Then the page should show the tiles "Soba, Mentsuyu"
