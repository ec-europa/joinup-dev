Feature: Encoding compatibility
In order to protect the integrity of the website
As a product owner
I want to make sure that the site integrity is not affected by naughty strings.

Scenario Outline: Insert weird data into the triple store
    Then I test for encoding compatibility

    Examples:
