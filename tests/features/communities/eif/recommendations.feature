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
      | title            | Test solution                        |
      | collection       | Test collection                      |
      | landing page     | http://foo-example.com/landing       |
      | webdav creation  | no                                   |
      | webdav url       | http://joinup.eu/solution/foo/webdav |
      | wiki             | http://example.wiki/foobar/wiki      |
      | state            | validated                            |
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
