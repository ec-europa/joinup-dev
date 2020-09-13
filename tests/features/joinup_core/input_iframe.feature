@api @group-b
Feature: Custom Joinup i-frames
  In order to be able to embed Joinup pages properly
  as a user of the website
  I need to be able to have the url properly structured.

  Background:
    Given the following collections:
      | title          | abstract                 | logo     | banner     | state     |
      | I-frame group  | Some abstract text       | logo.png | banner.jpg | validated |
      | I-framed group | Some other abstract text | logo.png | banner.jpg | validated |

  @javascript
  Scenario: Joinup allows embedding its own pages as i-frames.
    When I am not logged in
    And I go to the "I-framed group" collection
    Then the url should match "/collection/i-framed-group"

    Given custom_page content:
      | title            | body                                                      | collection    |
      | Joinup inception | <iframe src="/collection/i-framed-group?iframe="></iframe> | I-frame group |
    When I go to the "Joinup inception" custom page
    Then I see the "iframe" element with the "src" attribute set to "/collection/i-framed-group?iframe=1" in the "Content" region

  @javascript
  Scenario: Adding the proper query parameters allows to partially display the page.
    When I am not logged in
    And I visit "/collection/i-framed-group?iframe"
    Then I should not see the heading "I-framed group"
    And I should see the text "Some other abstract text"
