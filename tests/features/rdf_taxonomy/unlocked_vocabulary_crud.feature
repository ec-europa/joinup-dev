@api
Feature: As a use able to add/edit/delete taxonomy terms
  I am able to add/edit/delete taxonomy terms of unlocked vocabularies.

  Scenario: Add/edit/delete taxonomy terms of unlocked vocabularies.

    Given I am logged in as a user with the "create terms in policy_domain,edit terms in policy_domain,delete terms in policy_domain,access taxonomy overview" permissions
    When I visit "/admin/structure/taxonomy/manage/policy_domain/add"
    And I fill in "Name" with "Root Level Term"
    And I fill in "Description" with "Root level term description."
    And I select "<root>" from "Parent terms"
    Given I press "Save"
    Then I should see the following success messages:
      | success messages                  |
      | Created new term Root Level Term. |

    Given I visit "/admin/structure/taxonomy/manage/policy_domain/add"
    And I fill in "Name" with "2nd Level Term"
    And I select "Root Level Term" from "Parent terms"
    Given I press "Save"
    Then I should see the following success messages:
      | success messages                 |
      | Created new term 2nd Level Term. |

    Given I click "2nd Level Term"
    And I click "Edit"
    And I fill in "Name" with "Moved To Root"
    And I select "<root>" from "Parent terms"
    Given I press "Save"
    Then I should see the following success messages:
      | success messages            |
      | Updated term Moved To Root. |

    Given I click "Moved To Root"
    And I click "Edit"
    When I click "Delete"
    Then I should see the heading "Are you sure you want to delete the taxonomy term Moved To Root?"

    Given I press "Delete"
    Then I should see the following success messages:
      | success messages            |
      | Deleted term Moved To Root. |

    Given I visit "/admin/structure/taxonomy/manage/policy_domain/overview"
    Given I click "Root Level Term"
    And I click "Edit"
    When I click "Delete"
    Then I should see the heading "Are you sure you want to delete the taxonomy term Root Level Term?"

    Given I press "Delete"
    Then I should see the following success messages:
      | success messages            |
      | Deleted term Root Level Term. |
