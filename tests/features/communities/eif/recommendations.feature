@api @eif_community @group-b
Feature:
  As the owner of the EIF Toolbox
  in order to promote solutions we want to recommend
  I need to be able to present the solutions and the recommendations in the EIF Toolbox.

  Scenario: Recommendations page is only accessible through the EIF Toolbox solution.
    Given the following collection:
      | title | Test collection |
      | state | validated       |
    Given the following solution:
      | title           | Test solution                        |
      | collection      | Test collection                      |
      | landing page    | http://foo-example.com/landing       |
      | webdav creation | no                                   |
      | webdav url      | http://joinup.eu/solution/foo/webdav |
      | wiki            | http://example.wiki/foobar/wiki      |
      | state           | validated                            |
    When I am logged in as a moderator
    And I go to "/collection/test-collection/solution/test-solution"
    Then I should see the heading "Test solution"

    When I go to "/collection/test-collection/solution/test-solution/recommendations"
    Then the response status code should be 404

    # Visibility of recommendations page depends on the view access of the
    # parent solution. In order to avoid having two distinct scenario tags for
    # published and unpublished EIF Toolbox, transit the entity to the state
    # that deletes the validated version of the entity.
    Given the workflow state of the "EIF Toolbox" solution is changed to "blacklisted"
    And I am not logged in
    When I go to "/collection/nifo-collection/solution/eif-toolbox"
    Then I should see the heading "Sign in to continue"
    When I go to "/collection/nifo-collection/solution/eif-toolbox/recommendations"
    Then the response status code should be 404

  Scenario: The recommendations page lists the recommendations links.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF recommendations"

    # Sample check some links.
    And I should see the following links:
      | Recommendation 1 \| Underlying Principle 1: subsidiarity and proportionality |
      | Recommendation 2 \| Underlying Principle 2: openness                         |
      | Recommendation 3 \| Underlying Principle 2: openness                         |
      | Recommendation 4 \| Underlying Principle 2: openness                         |
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

  @javascript
  Scenario: Searching for anything, will not return eif recommendations as results.
    Given I am not logged in
    And I am on the homepage
    And I open the search bar by clicking on the search icon
    And I enter "Underlying Principle 4" in the search bar and press enter
    Then I should not see the text "Underlying Principle 4" in the "Content" region

  Scenario: The related terms are available in the page in 3 separate facets for filtering.
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region

    And the "eif principle" inline facet should allow selecting the following values:
      | Underlying Principle 1: subsidiarity and proportionality              |
      | Underlying Principle 2: openness                                      |
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

    And the "eif interoperability layer" inline facet should allow selecting the following values:
      | Interoperability Layer 1: Interoperability governance          |
      | Interoperability Layer 2: Integrated public service governance |
      | Interoperability Layer 3: Legal interoperability               |
      | Interoperability Layer 4: Organisational interoperability      |
      | Interoperability Layer 5: Semantic interoperability            |
      | Interoperability Layer 6: Technical interoperability           |

    And the "eif conceptual model" inline facet should allow selecting the following values:
      | Model                                                        |
      | Basic Component 2: Internal information sources and services |
      | Basic Component 3: Base registries                           |
      | Basic Component 4: Open data                                 |
      | Basic Component 5: Catalogues                                |
      | Basic Component 6: External information sources and services |
      | Basic Component 7: Security and privacy                      |

    When I click "Underlying Principle 2: openness" in the "eif principle" inline facet
    Then I should not see the "eif interoperability layer" inline facet
    And I should not see the "eif conceptual model" inline facet

    When I click "Underlying Principle 2: openness" in the "eif principle" inline facet
    Then I should see the "eif interoperability layer" inline facet
    And I should see the "eif conceptual model" inline facet

    When I click "Basic Component 4: Open data" in the "eif conceptual model" inline facet
    Then I should not see the "eif principle" inline facet
    And I should not see the "eif interoperability layer" inline facet
