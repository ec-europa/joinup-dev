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

    # Assert the full table.
    And the "eif recommendations" table should be:
      | Recommendation topics                         | EIF Pillars              | Recommendations                   |
      | Subsidiarity and proportionality              | Underlying Principle 1   | Recommendation 1                  |
      | Openness                                      | Underlying Principle 2   | Recommendation 2, 3, 4            |
      | Transparency                                  | Underlying Principle 3   | Recommendation 5                  |
      | Reusability                                   | Underlying Principle 4   | Recommendation 6, 7               |
      | Technological neutrality and data portability | Underlying Principle 5   | Recommendation 8, 9               |
      | User centricity                               | Underlying Principle 6   | Recommendation 10, 11, 12, 13     |
      | Inclusion and accessibility                   | Underlying Principle 7   | Recommendation 14                 |
      | Security and privacy                          | Underlying Principle 8   | Recommendation 15                 |
      | Multilingualism                               | Underlying Principle 9   | Recommendation 16                 |
      | Administrative simplification                 | Underlying Principle 10  | Recommendation 17                 |
      | Preservation of information                   | Underlying Principle 11  | Recommendation 18                 |
      | Assessment of effectiveness and efficiency    | Underlying Principle 12  | Recommendation 19                 |
      | Interoperability governance                   | Interoperability Layer 1 | Recommendation 20, 21, 22, 23, 24 |
      | Integrated public service governance          | Interoperability Layer 2 | Recommendation 25, 26             |
      | Legal interoperability                        | Interoperability Layer 3 | Recommendation 27                 |
      | Organisational interoperability               | Interoperability Layer 4 | Recommendation 28, 29             |
      | Semantic interoperability                     | Interoperability Layer 5 | Recommendation 30, 31, 32         |
      | Technical interoperability                    | Interoperability Layer 6 | Recommendation 33                 |
      | Model                                         | Model                    | Recommendation 34, 35             |
      | Internal information sources and services     | Basic Component 2        | Recommendation 36                 |
      | Base registries                               | Basic Component 3        | Recommendation 37, 38, 39, 40     |
      | Open data                                     | Basic Component 4        | Recommendation 41, 42, 43         |
      | Catalogues                                    | Basic Component 5        | Recommendation 44                 |
      | External information sources and services     | Basic Component 6        | Recommendation 45                 |
      | Security and privacy                          | Basic Component 7        | Recommendation 46, 47             |

    # Assert that recommendations are concatenated when grouped but only the first receive the keyword "Recommendation"
    # as part of the link.
    And I should see the link "Recommendation 20"
    And I should see the link "21"
    But I should not see the link "Recommendation 21"

    # Assert sub tables according to the facet.
    When I click "Conceptual model" in the "Content" region
    Then the "eif recommendations" table should be:
      | Recommendation topics                         | EIF Pillars              | Recommendations                   |
      | Model                                         | Model                    | Recommendation 34, 35             |
      | Internal information sources and services     | Basic Component 2        | Recommendation 36                 |
      | Base registries                               | Basic Component 3        | Recommendation 37, 38, 39, 40     |
      | Open data                                     | Basic Component 4        | Recommendation 41, 42, 43         |
      | Catalogues                                    | Basic Component 5        | Recommendation 44                 |
      | External information sources and services     | Basic Component 6        | Recommendation 45                 |
      | Security and privacy                          | Basic Component 7        | Recommendation 46, 47             |

    When I click "Interoperability layer" in the "Content" region
    Then the "eif recommendations" table should be:
      | Recommendation topics                         | EIF Pillars              | Recommendations                   |
      | Interoperability governance                   | Interoperability Layer 1 | Recommendation 20, 21, 22, 23, 24 |
      | Integrated public service governance          | Interoperability Layer 2 | Recommendation 25, 26             |
      | Legal interoperability                        | Interoperability Layer 3 | Recommendation 27                 |
      | Organisational interoperability               | Interoperability Layer 4 | Recommendation 28, 29             |
      | Semantic interoperability                     | Interoperability Layer 5 | Recommendation 30, 31, 32         |
      | Technical interoperability                    | Interoperability Layer 6 | Recommendation 33                 |

    When I click "Underlying principle" in the "Content" region
    Then the "eif recommendations" table should be:
      | Recommendation topics                         | EIF Pillars              | Recommendations                   |
      | Subsidiarity and proportionality              | Underlying Principle 1   | Recommendation 1                  |
      | Openness                                      | Underlying Principle 2   | Recommendation 2, 3, 4            |
      | Transparency                                  | Underlying Principle 3   | Recommendation 5                  |
      | Reusability                                   | Underlying Principle 4   | Recommendation 6, 7               |
      | Technological neutrality and data portability | Underlying Principle 5   | Recommendation 8, 9               |
      | User centricity                               | Underlying Principle 6   | Recommendation 10, 11, 12, 13     |
      | Inclusion and accessibility                   | Underlying Principle 7   | Recommendation 14                 |
      | Security and privacy                          | Underlying Principle 8   | Recommendation 15                 |
      | Multilingualism                               | Underlying Principle 9   | Recommendation 16                 |
      | Administrative simplification                 | Underlying Principle 10  | Recommendation 17                 |
      | Preservation of information                   | Underlying Principle 11  | Recommendation 18                 |
      | Assessment of effectiveness and efficiency    | Underlying Principle 12  | Recommendation 19                 |

    When I click "All recommendations" in the "Content" region
    Then the "eif recommendations" table should be:
      | Recommendation topics                         | EIF Pillars              | Recommendations                   |
      | Subsidiarity and proportionality              | Underlying Principle 1   | Recommendation 1                  |
      | Openness                                      | Underlying Principle 2   | Recommendation 2, 3, 4            |
      | Transparency                                  | Underlying Principle 3   | Recommendation 5                  |
      | Reusability                                   | Underlying Principle 4   | Recommendation 6, 7               |
      | Technological neutrality and data portability | Underlying Principle 5   | Recommendation 8, 9               |
      | User centricity                               | Underlying Principle 6   | Recommendation 10, 11, 12, 13     |
      | Inclusion and accessibility                   | Underlying Principle 7   | Recommendation 14                 |
      | Security and privacy                          | Underlying Principle 8   | Recommendation 15                 |
      | Multilingualism                               | Underlying Principle 9   | Recommendation 16                 |
      | Administrative simplification                 | Underlying Principle 10  | Recommendation 17                 |
      | Preservation of information                   | Underlying Principle 11  | Recommendation 18                 |
      | Assessment of effectiveness and efficiency    | Underlying Principle 12  | Recommendation 19                 |
      | Interoperability governance                   | Interoperability Layer 1 | Recommendation 20, 21, 22, 23, 24 |
      | Integrated public service governance          | Interoperability Layer 2 | Recommendation 25, 26             |
      | Legal interoperability                        | Interoperability Layer 3 | Recommendation 27                 |
      | Organisational interoperability               | Interoperability Layer 4 | Recommendation 28, 29             |
      | Semantic interoperability                     | Interoperability Layer 5 | Recommendation 30, 31, 32         |
      | Technical interoperability                    | Interoperability Layer 6 | Recommendation 33                 |
      | Model                                         | Model                    | Recommendation 34, 35             |
      | Internal information sources and services     | Basic Component 2        | Recommendation 36                 |
      | Base registries                               | Basic Component 3        | Recommendation 37, 38, 39, 40     |
      | Open data                                     | Basic Component 4        | Recommendation 41, 42, 43         |
      | Catalogues                                    | Basic Component 5        | Recommendation 44                 |
      | External information sources and services     | Basic Component 6        | Recommendation 45                 |
      | Security and privacy                          | Basic Component 7        | Recommendation 46, 47             |

  Scenario: Recommendations overview and each recommendation should show the EIF Toolbox header
    Given I am not logged in
    And I go to the "EIF Toolbox" solution
    When I click "Recommendations" in the "Left sidebar" region
    Then I should see the heading "EIF Toolbox"

    When I click "Recommendation 1"
    Then I should see the heading "EIF Toolbox"

  @javascript
  Scenario: Searching for anything, will not return eif recommendations as results.
    Given I am not logged in
    And I am on the homepage
    And I open the search bar by clicking on the search icon
    And I enter "Underlying Principle 4" in the search bar and press enter
    Then I should not see the text "Underlying Principle 4" in the "Content" region
