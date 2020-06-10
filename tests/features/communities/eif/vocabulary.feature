Feature:
  In order to be able to have the solutions categorized properly through the EIF Toolbox
  As the collection owner
  I need to have the EIF references available.

  Scenario: EIF References are available to view.
    Given I am not logged in
    When I visit "/taxonomy/term/http_e_f_fdata_ceuropa_ceu_fBC1"
    Then I should see the heading "Basic Component 1: Coordination function"
    And I should see the text "The coordination function ensures that needs are identified and appropriate services are invoked and orchestrated to provide a European public service."
