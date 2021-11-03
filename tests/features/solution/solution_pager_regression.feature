@api @terms @group-f
Feature: Test for a 'facets' regression on the solution overview page.
  # @see ISAICP-4188
  Scenario: Test that no pager is shown on the solution page when not needed.
    Given the following owner:
      | name              | type                  |
      | Apache Foundation | Private Individual(s) |
    And the following contact:
      | name  | Pierre Plezant             |
      | email | pierre.plezant@example.com |
    And the following solutions:
      | title                | description                | logo     | banner     | state     | owner             | contact information | solution type | topic       |
      | Apache               | Serving the web            | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Security audit tools | Automated test of security | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Drupal               | Content management         | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | MongoDB              | Free for all in box        | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | NodeJS               | Download all the packages  | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 1           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 2           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 3           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 4           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 5           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 6           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 7           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 8           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 9           | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
      | Solution 10          | More solution              | logo.png | banner.jpg | validated | Apache Foundation | Pierre Plezant      | Business      | E-inclusion |
    And the following distribution:
      | title       | Apache for MacOS    |
      | description | Apache distribution |
      | access url  | test.zip            |
      | parent      | Apache              |
    And I go to the homepage of the "Apache" solution
    Then I should not see the "Pager" region
