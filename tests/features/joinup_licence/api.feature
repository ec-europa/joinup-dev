Feature: Licence API
  In order to manage licences programmatically
  As a backend developer
  I need to be able to use the Collection API

  Scenario: Programmatically create a licence
    Given the following licence:
      | title       | Open licence              |
      | description | Licence agreement details |
      | type        | Public domain             |
    Then I should have 1 licence