@api
Feature:
  In order to be able to have the solutions categorized properly through the EIF Toolbox
  As the collection owner
  I need to have the EIF references available.

  Scenario: EIF References are available to view.
    Given I am not logged in
    When I visit "/taxonomy/term/http_e_f_fdata_ceuropa_ceu_fBC1"
    Then I should see the heading "Basic Component 1: Coordination function"
    And I should see the text "The coordination function ensures that needs are identified and appropriate services are invoked and orchestrated to provide a European public service."

  Scenario: EIF References field is accessible to moderators only.
    Given collection:
      | title | EIF Toolbox |
      | state | validated   |
    And solution:
      | title      | Some EIF solution |
      | state      | validated         |
      | collection | EIF Toolbox       |

    Given I am logged in as a facilitator of the "Some EIF solution" solution
    When I go to the "Some EIF solution" solution edit form
    Then the following fields should not be present "EIF Reference"

    Given I am logged in as a moderator
    When I go to the "Some EIF solution" solution edit form
    Then the following fields should be present "EIF Reference"

  Scenario: EIF references are not visible to the end user.
    Given collection:
      | title | EIF Toolbox |
      | state | validated   |
    And solution:
      | title         | Some EIF solution                                        |
      | state         | validated                                                |
      | collection    | EIF Toolbox                                              |
      | eif reference | Underlying Principle 1: subsidiarity and proportionality |

    When I go to the "Some EIF solution" solution
    Then I should not see the text "EIF Reference"
    When I click "About" in the "Left sidebar" region
    Then I should not see the text "EIF Reference"

    When I go to "/solutions"
    Then I should see the "Some EIF solution" tile
    But I should not see the text "EIF reference"
