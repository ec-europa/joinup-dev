@api
Feature: Input filter
  In order to maintain security
  As a user
  The HTML I can use in the WYSIWYG editor gets filtered

  Background:
    Given the following collection:
      | title | Netflix group |
      | logo  | logo.png      |
      | state | validated     |
    And news content:
      | title                 | headline                           | body                                                                                                                                                       | collection    | state     |
      | Jessica Jones returns | Netflix releases new Marvel series | value: <iframe width="560" height="315" src="https://www.youtube.com/embed/nWHUjuJ8zxE" frameborder="0" allowfullscreen></iframe> - format: content_editor | Netflix group | validated |
      | Luke cage             | Some shady iframe                  | value: <iframe width="50" height="50" src="https://www.example.com" ></iframe> - format: content_editor                                                    | Netflix group | validated |
      | Ragged Crying         | Ragged Crying                      | value: <h1>test h1</h1> <h2>test h2</h2> <h3>test h3</h3> <h4>test h4</h4> <h5>test h5</h5> <h6>test h6</h6> - format: content_editor                      | Netflix group | validated |

  Scenario: Videos
    When I go to the "Jessica Jones returns" news
    Then I should see the "iframe" element in the Content region
    Then I see the "iframe" element with the "src" attribute set to "https://www.youtube.com/embed/nWHUjuJ8zxE" in the "Content" region
    When I go to the "Luke cage" news
    Then I should not see the "iframe" element with the "src" attribute set to "https://www.example.com" in the "Content" region

  @javascript
  Scenario: Tags h1, h5, h6 can exist in a formatted text but the user does not have these options on the editor.
    When I am logged in as a moderator
    And I go to the "Ragged Crying" news
    Then I should see an "h1" element with the text "test h1" in the "Content" region
    Then I should see an "h2" element with the text "test h2" in the "Content" region
    Then I should see an "h3" element with the text "test h3" in the "Content" region
    Then I should see an "h4" element with the text "test h4" in the "Content" region
    Then I should see an "h5" element with the text "test h5" in the "Content" region
    Then I should see an "h6" element with the text "test h6" in the "Content" region

    # Ensure that the user does not have access to disallowed paragraph formats.
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the paragraph formats in the "Content" field should not contain the "h1, h5, h6" formats
