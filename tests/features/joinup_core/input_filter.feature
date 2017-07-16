@api
Feature: Input filter
  In order to maintain security
  As a user
  The HTML I can use in the WYSIWYG editor gets filtered

  Scenario: Videos
    Given the following collection:
      | title | Netflix group |
      | logo  | logo.png      |
      | state | validated     |
    And news content:
      | title                 | headline                           | body                                                                                                                                                       | collection    | state     |
      | Jessica Jones returns | Netflix releases new Marvel series | value: <iframe width="560" height="315" src="https://www.youtube.com/embed/nWHUjuJ8zxE" frameborder="0" allowfullscreen></iframe> - format: content_editor | Netflix group | validated |
      | Luke cage             | Some shady iframe                  | value: <iframe width="50" height="50" src="https://www.example.com" ></iframe> - format: content_editor                                                    | Netflix group | validated |
    When I go to the "Jessica Jones returns" news
    Then I should see the "iframe" element in the Content region
    When I go to the "Luke cage" news
    Then I should not see the "iframe" element in the Content region
