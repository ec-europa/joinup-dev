@group-d
Feature:
  As an owner of a website
  When I visit non existing pages
  I should get a 'Page not found' without exceptions

  # This is a regression test on accessing edit route of non existing rdf entities.
  # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-3227
  Scenario: Accessing the edit route of a non existing rdf entity should raise a 404.
    When I go to "/rdf_entity/non-existing-id/edit"
    Then I should see the heading "Page not found"

  Scenario: Accessing a non existing page, should show a custom 404 page.
    When I go to "/this-url-does-not-exist"
    Then I should see the heading "Page not found"
    And I should see the text "The page you are looking for does not exist; It may have been moved, or removed altogether. You might want to:"
    And I should see the link "search function"
    And I should see the link "home page"
