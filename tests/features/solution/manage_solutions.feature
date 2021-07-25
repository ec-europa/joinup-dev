@api @group-a
Feature: As a moderator I want to be able to move solutions to other community.

  Scenario: Moderators can move a solution to a different community.

    Given the following communities:
      | uri                            | title       | state     |
      | http://example.com/source      | Source      | validated |
      | http://example.com/destination | Destination | validated |

    And solutions:
      | title      | community | state     |
      | Solution 1 | Source     | validated |
      | Solution 2 | Source     | validated |
      | Solution 3 | Source     | validated |
      | Solution 4 | Source     | validated |

    # Only moderators should be able to access this feature.
    Given I am an anonymous user
    When I go to the homepage of the "Source" community
    Then I should not see the link "Manage solutions"
    When I go to "/rdf_entity/http_e_f_fexample_ccom_fsource/change-community"
    Then I should see the heading "Sign in to continue"

    Given I am logged in as an "authenticated"
    When I go to the homepage of the "Source" community
    Then I should not see the link "Manage solutions"
    When I go to "/rdf_entity/http_e_f_fexample_ccom_fsource/change-community"
    Then I should get an access denied error

    Given I am logged in as a moderator

    # The 'Manage solutions' link shows only on communities.
    When I go to the homepage of the "Solution 1" solution
    Then I should not see the link "Manage solutions"

    When I go to the homepage of the "Source" community
    Then I should see the link "Manage solutions"
    When I click "Manage solutions"
    Then I should see the heading "Manage solutions"

    Then I select the "Solution 1" row
    And I select the "Solution 4" row
    And I select "Move to other community" from "Action"

    When I press "Apply to selected items"
    Then I should see the heading "Select a destination community"
    And I should see the following lines of text:
      | The following solutions from Source community will be moved to a new community: |
      | Solution 1                                                                        |
      | Solution 4                                                                        |

    # Trying to move solutions to the same community should result in an error.
    When I fill in "Select the destination community" with "Source"
    And I press "Move solutions"
    Then I should see the error message "The destination community cannot be the same as the source community."

    When I fill in "Select the destination community" with "Destination"
    And I press "Move solutions"
    Then I should see the following success messages:
      | success messages                                   |
      | Solution Solution 1 has been moved to Destination. |
      | Solution Solution 4 has been moved to Destination. |

    When I go to the homepage of the "Destination" community
    Then I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 4 |

    When I go to the homepage of the "Source" community
    Then I should see the following tiles in the correct order:
      | Solution 2 |
      | Solution 3 |
