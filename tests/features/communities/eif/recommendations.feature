@api @eif_community @group-b
Feature:
  As the owner of the EIF Toolbox
  in order to promote solutions we want to recommend
  I need to be able to present the solutions and the recommendations in the EIF toolbox.

  Scenario: The recommendations page lists the recommendations links.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF recommendations"

    # Sample check some links.
    And I should see the following links:
      | Recommendation 1 \| Underlying Principle 1: subsidiarity and proportionality |
      | Recommendation 2 \| Underlying Principle 2: openess                          |
      | Recommendation 3 \| Underlying Principle 2: openess                          |
      | Recommendation 4 \| Underlying Principle 2: openess                          |
      | Recommendation 5 \| Underlying Principle 3: transparency                     |
      | Recommendation 6 \| Underlying Principle 4: reusability                      |
      | Recommendation 7 \| Underlying Principle 4: reusability                      |

  Scenario: Recommendations overview and each recommendation should show the EIF Toolbox header
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF Toolbox"

    When I click "Recommendation 1 | Underlying Principle 1: subsidiarity and proportionality"
    Then I should see the heading "EIF Toolbox"

  Scenario: The related terms are available in the page in 3 separate facets for filtering.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region

    And the "eif principle" select facet should contain the following options:
      | - All -                                                               |
      | Underlying Principle 1: subsidiarity and proportionality              |
      | Underlying Principle 2: openess                                       |
      | Underlying Principle 3: transparency                                  |
      | Underlying Principle 4: reusability                                   |
      | Underlying Principle 5: technological neutrality and data portability |
      | Underlying Principle 6: user centricity                               |
      | Underlying Principle 7: inclusion and accessibility                   |
      | Underlying Principle 8: security and privacy                          |
      | Underlying Principle 9: multilingualism                               |
      | Underlying Principle 10: administrative simplification                |
      | Underlying Principle 11: preservation of information                  |
      | Underlying Principle 12: assessment of effectiveness and efficiency   |

    And the "eif interoperability layer" select facet should contain the following options:
      | - All -                                                        |
      | Interoperability Layer 1: Interoperability governance          |
      | Interoperability Layer 2: Integrated public service governance |
      | Interoperability Layer 3: Legal interoperability               |
      | Interoperability Layer 4: Organisational interoperability      |
      | Interoperability Layer 5: Semantic interoperability            |
      | Interoperability Layer 6: Technical interoperability           |

    And the "eif conceptual model" select facet should contain the following options:
      | - All -                                 |
      | Model                                   |
      | Basic Component 3: Base registries      |
      | Basic Component 4: Open data            |
      | Basic Component 7: Security and privacy |
