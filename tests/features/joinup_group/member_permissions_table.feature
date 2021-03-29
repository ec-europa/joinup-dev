@api @terms
Feature: Group member permissions table
  In order to get an overview of which actions I can take in a group
  As a member of a collection or solution
  I need to be able to see which permissions I have

  Scenario:
    Given users:
      | Username         |
      | Mustrum Ridcully |
      | A. A. Dinwiddie  |
      | Henry Porter     |
    Given the following collections:
      | title                                       | state     | content creation         | moderation |
      | Applied astrology                           | validated | facilitators and authors | yes        |
      | Illiberal studies                           | validated | facilitators and authors | no         |
      | Approximate accuracy                        | validated | members                  | yes        |
      | Dust, miscellaneous particles and filaments | validated | members                  | no         |
      | Creative uncertainty                        | validated | registered users         | yes        |
      | Woolly thinking                             | validated | registered users         | no         |
    And the following solutions:
      | title                         | state     | content creation         | moderation |
      | Applied anthropics            | validated | facilitators and authors | yes        |
      | Extreme horticulture          | validated | facilitators and authors | no         |
      | Prehumous morbid bibliomancy  | validated | registered users         | yes        |
      | Posthumous morbid bibliomancy | validated | registered users         | no         |
    And the following collection user memberships:
      | collection                                  | user             | roles       |
      | Applied astrology                           | Mustrum Ridcully | facilitator |
      | Illiberal studies                           | Mustrum Ridcully | facilitator |
      | Approximate accuracy                        | Mustrum Ridcully | facilitator |
      | Dust, miscellaneous particles and filaments | Mustrum Ridcully | facilitator |
      | Creative uncertainty                        | Mustrum Ridcully | facilitator |
      | Woolly thinking                             | Mustrum Ridcully | facilitator |
      | Applied astrology                           | A. A. Dinwiddie  | author      |
      | Illiberal studies                           | A. A. Dinwiddie  | author      |
      | Approximate accuracy                        | A. A. Dinwiddie  | author      |
      | Dust, miscellaneous particles and filaments | A. A. Dinwiddie  | author      |
      | Creative uncertainty                        | A. A. Dinwiddie  | author      |
      | Woolly thinking                             | A. A. Dinwiddie  | author      |
      | Applied astrology                           | Henry Porter     |             |
      | Illiberal studies                           | Henry Porter     |             |
      | Approximate accuracy                        | Henry Porter     |             |
      | Dust, miscellaneous particles and filaments | Henry Porter     |             |
      | Creative uncertainty                        | Henry Porter     |             |
      | Woolly thinking                             | Henry Porter     |             |
    And the following solution user memberships:
      | solution                      | user             | roles       |
      | Applied anthropics            | Mustrum Ridcully | facilitator |
      | Extreme horticulture          | Mustrum Ridcully | facilitator |
      | Prehumous morbid bibliomancy  | Mustrum Ridcully | facilitator |
      | Posthumous morbid bibliomancy | Mustrum Ridcully | facilitator |
      | Applied anthropics            | A. A. Dinwiddie  | author      |
      | Extreme horticulture          | A. A. Dinwiddie  | author      |
      | Prehumous morbid bibliomancy  | A. A. Dinwiddie  | author      |
      | Posthumous morbid bibliomancy | A. A. Dinwiddie  | author      |
      | Applied anthropics            | Henry Porter     |             |
      | Extreme horticulture          | Henry Porter     |             |
      | Prehumous morbid bibliomancy  | Henry Porter     |             |
      | Posthumous morbid bibliomancy | Henry Porter     |             |

    # Collection. Content creation: authors and facilitators. Moderated.
    And I am on the members page of "Applied astrology"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     |        | ✓      | ✓           | ✓     |
      | Can publish content        |        | ✓      | ✓           | ✓     |

    # Quick check to verify the permissions are actually matching what is displayed in the table.
    Given I am logged in as a member of the "Applied astrology" collection
    When I go to the homepage of the "Applied astrology" collection
    # Can not start a discussion.
    Then I should not see the link "Add discussion"
    # Can not propose or publish content.
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"


    # Collection. Content creation: authors and facilitators. Not moderated.
    And I am on the members page of "Illiberal studies"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     |        | ✓      | ✓           | ✓     |
      | Can publish content        |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Illiberal studies" collection
    When I go to the homepage of the "Illiberal studies" collection
    # Can not start a discussion.
    Then I should not see the link "Add discussion"
    # Can not propose or publish content.
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"


    # Collection. Content creation: members. Moderated.
    Given I am on the members page of "Approximate accuracy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                            | Member | Author | Facilitator | Owner |
      | Can view published content                            | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion                                | ✓      | ✓      | ✓           | ✓     |
      | Can propose content for publication, pending approval | ✓      |        |             |       |
      | Can publish content without approval                  |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Approximate accuracy" collection
    When I go to the homepage of the "Approximate accuracy" collection
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can propose content but not publish.
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"

    # Collection. Content creation: members. Not moderated.
    Given I am on the members page of "Dust, miscellaneous particles and filaments"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     | ✓      | ✓      | ✓           | ✓     |
      | Can publish content        | ✓      | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Dust, miscellaneous particles and filaments" collection
    When I go to the homepage of the "Dust, miscellaneous particles and filaments" collection
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can publish content but not propose.
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"

    # Collection. Content creation: any user. Moderated.
    Given I am on the members page of "Creative uncertainty"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                            | Member | Author | Facilitator | Owner |
      | Can view published content                            | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion                                | ✓      | ✓      | ✓           | ✓     |
      | Can propose content for publication, pending approval | ✓      |        |             |       |
      | Can publish content without approval                  |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Creative uncertainty" collection
    When I go to the homepage of the "Creative uncertainty" collection
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can propose content but not publish.
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"

    # Collection. Content creation: any user. Not moderated.
    Given I am on the members page of "Woolly thinking"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     | ✓      | ✓      | ✓           | ✓     |
      | Can publish content        | ✓      | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Woolly thinking" collection
    When I go to the homepage of the "Woolly thinking" collection
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can publish content but not propose.
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"

    # Solution. Content creation: authors and facilitators. Moderated.
    Given I am on the members page of "Applied anthropics"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     |        | ✓      | ✓           | ✓     |
      | Can publish content        |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Applied anthropics" solution
    When I go to the homepage of the "Applied anthropics" solution
    # Can not start a discussion.
    Then I should not see the link "Add discussion"
    # Can not propose or publish content.
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"


    # Solution. Content creation: authors and facilitators. Non-moderated.
    Given I am on the members page of "Extreme horticulture"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     |        | ✓      | ✓           | ✓     |
      | Can publish content        |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Extreme horticulture" solution
    When I go to the homepage of the "Extreme horticulture" solution
    # Can not start a discussion.
    Then I should not see the link "Add discussion"
    # Can not propose or publish content.
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"


    # Solution. Content creation: any user. Moderated.
    Given I am on the members page of "Prehumous morbid bibliomancy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                            | Member | Author | Facilitator | Owner |
      | Can view published content                            | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion                                | ✓      | ✓      | ✓           | ✓     |
      | Can propose content for publication, pending approval | ✓      |        |             |       |
      | Can publish content without approval                  |        | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Prehumous morbid bibliomancy" solution
    When I go to the homepage of the "Prehumous morbid bibliomancy" solution
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can propose content but not publish.
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"

    # Solution. Content creation: any user. Non-moderated.
    Given I am on the members page of "Posthumous morbid bibliomancy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                 | Member | Author | Facilitator | Owner |
      | Can view published content | ✓      | ✓      | ✓           | ✓     |
      | Can start a discussion     | ✓      | ✓      | ✓           | ✓     |
      | Can publish content        | ✓      | ✓      | ✓           | ✓     |

    Given I am logged in as a member of the "Posthumous morbid bibliomancy" solution
    When I go to the homepage of the "Posthumous morbid bibliomancy" solution
    # Can start a discussion.
    And I click "Add discussion"
    Then I should see the button "Publish"
    # Can publish content but not propose.
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"

  # The permissions table should not be accessible for non-public groups.
  Scenario: Access the membership permissions information table
    Given the following collections:
      | title                | state       |
      | Valid Bibliomancy    | validated   |
      | Draft Bibliomancy    | draft       |
      | Proposed Bibliomancy | proposed    |
    And the following solutions:
      | title             | state       |
      | Valid Dynamics    | validated   |
      | Draft Dynamics    | draft       |
      | Proposed Dynamics | proposed    |
      | Dark Dynamics     | blacklisted |
    When I go to the member permissions table of "Draft Bibliomancy"
    Then I should see the heading "Sign in to continue"
    When I go to the member permissions table of "Proposed Bibliomancy"
    Then I should see the heading "Sign in to continue"
    When I go to the member permissions table of "Valid Bibliomancy"
    Then I should see the heading "Member permissions"
    When I go to the member permissions table of "Draft Dynamics"
    Then I should see the heading "Sign in to continue"
    When I go to the member permissions table of "Proposed Dynamics"
    Then I should see the heading "Sign in to continue"
    When I go to the member permissions table of "Dark Dynamics"
    Then I should see the heading "Sign in to continue"
    When I go to the member permissions table of "Valid Dynamics"
    Then I should see the heading "Member permissions"
