@api @eif_community @group-b
Feature:
  As the owner of the EIF Toolbox
  in order to promote solutions we want to recommend
  I need to be able to present the solutions and the recommendations in the EIF toolbox.

  Scenario: The recommendations page lists the recommendations links.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "Recommendations"

    # Sample check some links.
    And I should see the following links:
      | Basic Component 1: Coordination function               |
      | Interoperability Layer 1: Interoperability governance  |
      | Recommendation 1                                       |
      | Underlying Principle 10: administrative simplification |

  Scenario: Recommendations overview and each recommendation should show the EIF Toolbox header
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF Toolbox"

    When I click "Basic Component 1: Coordination function"
    Then I should see the heading "EIF Toolbox"
