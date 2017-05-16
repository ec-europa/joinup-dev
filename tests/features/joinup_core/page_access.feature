Feature:
  As an owner of a website
  When I visit non existing pages
  I should get a 'Page not found' without exceptions

  # This is a regression test on accessing edit route of non existing rdf entities.
  # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3227
  Scenario: Accessing the edit route of a non existing rdf entity should raise a 403.
    When I go to "/rdf_entity/non-existing-id/edit"
    Then I should see the heading "Page not found"
