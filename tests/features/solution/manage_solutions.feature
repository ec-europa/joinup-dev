@api @group-f
Feature: As a moderator I want to be able to move solutions to other collection.

  Scenario: Moderators can move a solution to a different collection.

    Given the following collections:
      | uri                            | title       | state     |
      | http://example.com/source      | Source      | validated |
      | http://example.com/destination | Destination | validated |

    And solutions:
      | title      | collection | state     |
      | Solution 1 | Source     | validated |
      | Solution 2 | Source     | validated |
      | Solution 3 | Source     | validated |
      | Solution 4 | Source     | validated |

    # Only moderators should be able to access this feature.
    Given I am an anonymous user
    When I go to the homepage of the "Source" collection
    Then I should not see the link "Manage solutions"
    When I go to "/rdf_entity/http_e_f_fexample_ccom_fsource/change-collection"
    Then I should see the heading "Sign in to continue"

    Given I am logged in as an "authenticated"
    When I go to the homepage of the "Source" collection
    Then I should not see the link "Manage solutions"
    When I go to "/rdf_entity/http_e_f_fexample_ccom_fsource/change-collection"
    Then I should get an access denied error

    Given I am logged in as a moderator

    # The 'Manage solutions' link shows only on collections.
    When I go to the homepage of the "Solution 1" solution
    Then I should not see the link "Manage solutions"

    When I go to the homepage of the "Source" collection
    Then I should see the link "Manage solutions"
    When I click "Manage solutions"
    Then I should see the heading "Manage solutions"

    Then I select the "Solution 1" row
    And I select the "Solution 4" row
    And I select "Move to other collection" from "Action"

    When I press "Apply to selected items"
    Then I should see the heading "Select a destination collection"
    And I should see the following lines of text:
      | The following solutions from Source collection will be moved to a new collection: |
      | Solution 1                                                                        |
      | Solution 4                                                                        |

    # Trying to move solutions to the same collection should result in an error.
    When I fill in "Select the destination collection" with "Source"
    And I press "Move solutions"
    Then I should see the error message "The destination collection cannot be the same as the source collection."

    When I fill in "Select the destination collection" with "Destination"
    And I press "Move solutions"
    Then I should see the following success messages:
      | success messages                                   |
      | Solution Solution 1 has been moved to Destination. |
      | Solution Solution 4 has been moved to Destination. |

    When I go to the homepage of the "Destination" collection
    Then I should see the following tiles in the correct order:
      | Solution 1 |
      | Solution 4 |

    When I go to the homepage of the "Source" collection
    Then I should see the following tiles in the correct order:
      | Solution 2 |
      | Solution 3 |
