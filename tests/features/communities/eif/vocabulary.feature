@api @group-d
Feature:
  In order to be able to have the solutions categorized properly through the EIF Toolbox
  As the collection owner
  I need to have the EIF recommendations available.

  Scenario: EIF recommendations are available to view.
    Given I am not logged in
    When I visit "/taxonomy/term/http_e_f_fdata_ceuropa_ceu_fBC1"
    Then I should see the heading "Basic Component 1: Coordination function"
    And I should see the text "The coordination function ensures that needs are identified and appropriate services are invoked and orchestrated to provide a European public service."

  Scenario: EIF recommendations field is accessible to moderators only.
    Given collection:
      | title | EIF Toolbox |
      | state | validated   |
    And solution:
      | title      | Some EIF solution |
      | state      | validated         |
      | collection | EIF Toolbox       |

    Given I am logged in as a facilitator of the "Some EIF solution" solution
    When I go to the edit form of the "Some EIF solution" solution
    Then the following fields should not be present "EIF reference, EIF category"

    Given I am logged in as a moderator
    When I go to the edit form of the "Some EIF solution" solution
    Then the following fields should be present "EIF reference, EIF category"

    When I press "Publish"

  Scenario: EIF recommendations are not visible to the end user.
    Given collection:
      | title | EIF Toolbox |
      | state | validated   |
    And solution:
      | title         | Some EIF solution |
      | state         | validated         |
      | collection    | EIF Toolbox       |
      | eif reference | Recommendation 1  |
      | eif category  | Common services   |

    When I go to the "Some EIF solution" solution
    Then I should not see the text "EIF reference"
    When I click "About" in the "Left sidebar" region
    Then I should not see the text "EIF reference"

    When I go to "/solutions"
    Then I should see the "Some EIF solution" tile
    But I should not see the text "EIF reference"

  @terms
  Scenario: Solutions referencing an EIF term should appear in the corresponding page.
    Given collection:
      | title | EIF Toolbox |
      | state | validated   |
    And solutions:
      | title    | state     | topic      | collection  | eif reference                      |
      | Balker   | validated |            | EIF Toolbox | Recommendation 1, Recommendation 2 |
      | Corridor | validated |            | EIF Toolbox | Recommendation 1                   |
      | Lager    | validated | Demography | EIF Toolbox |                                    |

    # Underlying Principle 1.
    When I go to "/taxonomy/term/http_e_f_fdata_ceuropa_ceu_fRec1"
    Then I should see the "Balker" tile
    And I should see the "Corridor" tile
    But I should not see the "Lager" tile

    # Underlying Principle 2
    When I go to "/taxonomy/term/http_e_f_fdata_ceuropa_ceu_fRec2"
    Then I should see the "Balker" tile
    But I should not see the "Corridor" tile
    And I should not see the "Lager" tile

    # Topic should not list entities as well.
    Given I am logged in as a moderator
    When I click "RDF ID converter" in the "Administration toolbar" region
    And I fill in "RDF entity ID or a URL" with "http://joinup.eu/ontology/topic#demography"
    And I press "Go!"
    Then I should see the heading "Demography"
    But I should not see the "Lager" tile
