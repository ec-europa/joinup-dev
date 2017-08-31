# Tests notifications for solutions. This file does not include template 1.
# Template 1 is tested in the add_solution.feature already.
@api @terms @email
Feature: Solution notifications
  In order to manage solutions
  As a user of the website
  I need to receive email that inform me regarding solution transitions.

  Scenario: Notifications are sent every time a related transition is applied to a solution.
    Given the following collection:
      | title | Collection of random solutions |
      | logo  | logo.png                       |
      | state | validated                      |
    And the following owner:
      | name               | type                  |
      | Karanikolas Kitsos | Private Individual(s) |
    And the following contact:
      | name  | Information Desk             |
      | email | information.desk@example.com |
    And users:
      | Username     | Roles     | First name | Family name |
      | Pat Harper   | moderator | Pat        | Harper      |
      | Ramiro Myers |           | Ramiro     | Myers       |
      | Edith Poole  |           | Edith      | Poole       |
    And the following solutions:
      | title                                                 | author       | description | logo     | banner     | owner              | contact information | state            | policy domain | solution type     | collection                     |
      | Solution notification to propose changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to request deletion             | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to approve deletion             | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | deletion request | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to reject deletion              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | deletion request | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to blacklist                    | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to publish from blacklisted     | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | blacklisted      | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to request changes              | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to propose from request changes | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | needs update     | E-inclusion   | [ABB169] Business | Collection of random solutions |
      | Solution notification to delete                       | Ramiro Myers | Sample text | logo.png | banner.jpg | Karanikolas Kitsos | Information Desk    | validated        | E-inclusion   | [ABB169] Business | Collection of random solutions |

