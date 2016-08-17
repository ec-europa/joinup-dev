@api
Feature: Solution API
  In order to manage solutions programmatically
  As a backend developer
  I need to be able to use the Solution API

  Scenario: Programmatically create a solution
    Given the following organisation:
      | name | Angelos Agathe |
    And the following contact:
      | name  | Placide             |
      | email | Placide@example.com |
    And users:
      | name              | pass |
      | Solution API user | pass |
    And the following solutions:
      | title                      | description                | logo     | banner     | owner          | contact information | state |
      | Azure Ship                 | Azure ship                 | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
      | The Last Illusion          | The Last Illusion          | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
      | Rose of Doors              | Rose of Doors              | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
      | The Ice's Secrets          | The Ice's Secrets          | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
      | The Guardian of the Stream | The Guardian of the Stream | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
      | Flames in the Swords       | Flames in the Swords       | logo.png | banner.jpg | Angelos Agathe | Placide             | draft |
