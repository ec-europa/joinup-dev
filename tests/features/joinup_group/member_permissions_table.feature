@api @terms @group-d
Feature: Group member permissions table
  In order to get an overview of which actions I can take in a group
  As a member of a collection or solution
  I need to be able to see which permissions I have

  Scenario:
    Given users:
      | Username         |
      | Horace Worblehat |
      | Ponder Stibbons  |
      | Henry Porter     |
      | Rincewind        |
      | Dr. John Hicks   |
      | Hex              |
      | Mustrum Ridcully |
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
      | Approximate accuracy                        | Horace Worblehat |             |
      | Dust, miscellaneous particles and filaments | Ponder Stibbons  |             |
      | Creative uncertainty                        | Henry Porter     |             |
      | Woolly thinking                             | Rincewind        |             |
      | Applied astrology                           | Mustrum Ridcully | facilitator |
      | Illiberal studies                           | Mustrum Ridcully | facilitator |
      | Approximate accuracy                        | Mustrum Ridcully | facilitator |
      | Dust, miscellaneous particles and filaments | Mustrum Ridcully | facilitator |
      | Creative uncertainty                        | Mustrum Ridcully | facilitator |
      | Woolly thinking                             | Mustrum Ridcully | facilitator |
    And the following solution user memberships:
      | solution                      | user             | roles       |
      | Prehumous morbid bibliomancy  | Dr. John Hicks   |             |
      | Posthumous morbid bibliomancy | Hex              |             |
      | Applied anthropics            | Mustrum Ridcully | facilitator |
      | Extreme horticulture          | Mustrum Ridcully | facilitator |
      | Prehumous morbid bibliomancy  | Mustrum Ridcully | facilitator |
      | Posthumous morbid bibliomancy | Mustrum Ridcully | facilitator |

    # Collection. Content creation: authors and facilitators. Moderated.
    And I am on the members page of "Applied astrology"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          |        | ✓      | ✓           | ✓     |
      | Publish content                             |        | ✓      | ✓           | ✓     |
      | Update own published content                |        | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          |        | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    # Quick check to verify the permissions are actually matching what is
    # displayed in the table. Only the most common case ("member") is checked.
    # This is already covered in other scenarios, but having a check here will
    # alert us to update the tables if permissions change.
    Given I am logged in as a member of the "Applied astrology" collection
    When I go to the homepage of the "Applied astrology" collection
    # Can not propose or publish content.
    Then I should not see the link "Add discussion"
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"
    Given event content:
      | title             | state | collection        | author           |
      | The Poor Scholars | draft | Applied astrology | Mustrum Ridcully |
    When I go to the content page of the type event with the title "The Poor Scholars"
    Then I should get an access denied error

    # Collection. Content creation: authors and facilitators. Not moderated.
    And I am on the members page of "Illiberal studies"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          |        | ✓      | ✓           | ✓     |
      | Publish content                             |        | ✓      | ✓           | ✓     |
      | Update own published content                |        | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          |        | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as a member of the "Illiberal studies" collection
    When I go to the homepage of the "Illiberal studies" collection
    # Can not propose or publish content.
    Then I should not see the link "Add discussion"
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"
    Given event content:
      | title    | state | collection        | author           |
      | Rag Week | draft | Illiberal studies | Mustrum Ridcully |
    When I go to the content page of the type event with the title "Rag Week"
    Then I should get an access denied error


    # Collection. Content creation: members. Moderated.
    Given I am on the members page of "Approximate accuracy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                                 | Member | Author | Facilitator | Owner |
      | View published content                                     | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users                  |        |        | ✓           | ✓     |
      | Start a discussion                                         | ✓      | ✓      | ✓           | ✓     |
      | Propose content for publication, pending approval          | ✓      |        |             |       |
      | Approve proposed content for publication                   |        |        | ✓           | ✓     |
      | Publish content without approval                           |        | ✓      | ✓           | ✓     |
      | Propose changes to own published content, pending approval | ✓      |        |             |       |
      | Approve proposed changes to published content              |        |        | ✓           | ✓     |
      | Update own published content without approval              |        | ✓      | ✓           | ✓     |
      | Update any content                                         |        |        | ✓           | ✓     |
      | Request changes on content from other users                |        |        | ✓           | ✓     |
      | Request deletion of own content, pending approval          | ✓      |        |             |       |
      | Approve requested deletion of content                      |        |        | ✓           | ✓     |
      | Delete own content without approval                        |        | ✓      | ✓           | ✓     |
      | Delete any content                                         |        |        | ✓           | ✓     |
      | Manage users                                               |        |        | ✓           | ✓     |

    Given I am logged in as "Horace Worblehat"
    When I go to the homepage of the "Approximate accuracy" collection
    # Can propose content but not publish.
    And I click "Add discussion"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    Given event content:
      | title                          | state     | collection           | author           |
      | Election of Boy Archchancellor | validated | Approximate accuracy | Horace Worblehat |
      | The Wizards' Excuse Me         | draft     | Approximate accuracy | Mustrum Ridcully |
    When I go to the edit form of the "Election of Boy Archchancellor" event
    Then I should see the button "Request deletion"
    And I should see the button "Propose changes"
    But I should not see the link "Delete"
    And I should not see the button "Update"
    When I go to the content page of the type event with the title "The Wizards' Excuse Me"
    Then I should get an access denied error

    # Collection. Content creation: members. Not moderated.
    Given I am on the members page of "Dust, miscellaneous particles and filaments"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          | ✓      | ✓      | ✓           | ✓     |
      | Publish content                             | ✓      | ✓      | ✓           | ✓     |
      | Update own published content                | ✓      | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          | ✓      | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as "Ponder Stibbons"
    When I go to the homepage of the "Dust, miscellaneous particles and filaments" collection
    # Can publish content but not propose.
    And I click "Add discussion"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    Given event content:
      | title                                               | state     | collection                                  | author           |
      | Beating the bounds                                  | validated | Dust, miscellaneous particles and filaments | Ponder Stibbons  |
      | Archchancellor Bowell's Remembrance's Bun and Penny | draft     | Dust, miscellaneous particles and filaments | Mustrum Ridcully |
    When I go to the edit form of the "Beating the bounds" event
    Then I should see the link "Delete"
    And I should see the button "Update"
    But I should not see the button "Request deletion"
    And I should not see the button "Propose changes"
    When I go to the content page of the type event with the title "Archchancellor Bowell's Remembrance's Bun and Penny"
    Then I should get an access denied error

    # Collection. Content creation: any user. Moderated.
    Given I am on the members page of "Creative uncertainty"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                                 | Member | Author | Facilitator | Owner |
      | View published content                                     | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users                  |        |        | ✓           | ✓     |
      | Start a discussion                                         | ✓      | ✓      | ✓           | ✓     |
      | Propose content for publication, pending approval          | ✓      |        |             |       |
      | Approve proposed content for publication                   |        |        | ✓           | ✓     |
      | Publish content without approval                           |        | ✓      | ✓           | ✓     |
      | Propose changes to own published content, pending approval | ✓      |        |             |       |
      | Approve proposed changes to published content              |        |        | ✓           | ✓     |
      | Update own published content without approval              |        | ✓      | ✓           | ✓     |
      | Update any content                                         |        |        | ✓           | ✓     |
      | Request changes on content from other users                |        |        | ✓           | ✓     |
      | Request deletion of own content, pending approval          | ✓      |        |             |       |
      | Approve requested deletion of content                      |        |        | ✓           | ✓     |
      | Delete own content without approval                        |        | ✓      | ✓           | ✓     |
      | Delete any content                                         |        |        | ✓           | ✓     |
      | Manage users                                               |        |        | ✓           | ✓     |

    Given I am logged in as "Henry Porter"
    When I go to the homepage of the "Creative uncertainty" collection
    # Can propose content but not publish.
    And I click "Add discussion"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    Given event content:
      | title                       | state     | collection           | author           |
      | The Convivium               | validated | Creative uncertainty | Henry Porter     |
      | The Hunting of the Megapode | draft     | Creative uncertainty | Mustrum Ridcully |
    When I go to the edit form of the "The Convivium" event
    Then I should see the button "Request deletion"
    And I should see the button "Propose changes"
    But I should not see the link "Delete"
    And I should not see the button "Update"
    When I go to the content page of the type event with the title "The Hunting of the Megapode"
    Then I should get an access denied error

    # Collection. Content creation: any user. Not moderated.
    Given I am on the members page of "Woolly thinking"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          | ✓      | ✓      | ✓           | ✓     |
      | Publish content                             | ✓      | ✓      | ✓           | ✓     |
      | Update own published content                | ✓      | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          | ✓      | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as "Rincewind"
    When I go to the homepage of the "Woolly thinking" collection
    # Can publish content but not propose.
    And I click "Add discussion"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    Given event content:
      | title                                     | state     | collection      | author           |
      | Gaudy night                               | validated | Woolly thinking | Rincewind        |
      | Archchancellor Preserved Bigger's Bequest | draft     | Woolly thinking | Mustrum Ridcully |
    When I go to the edit form of the "Gaudy night" event
    Then I should see the link "Delete"
    And I should see the button "Update"
    But I should not see the button "Request deletion"
    And I should not see the button "Propose changes"
    When I go to the content page of the type event with the title "Archchancellor Preserved Bigger's Bequest"
    Then I should get an access denied error

    # Solution. Content creation: authors and facilitators. Moderated.
    Given I am on the members page of "Applied anthropics"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          |        | ✓      | ✓           | ✓     |
      | Publish content                             |        | ✓      | ✓           | ✓     |
      | Update own published content                |        | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          |        | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as a member of the "Applied anthropics" solution
    When I go to the homepage of the "Applied anthropics" solution
    # Can not propose or publish content.
    Then I should not see the link "Add discussion"
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"
    Given event content:
      | title        | state | solution           | author           |
      | Scrawn Money | draft | Applied anthropics | Mustrum Ridcully |
    When I go to the content page of the type event with the title "Scrawn Money"
    Then I should get an access denied error


    # Solution. Content creation: authors and facilitators. Non-moderated.
    Given I am on the members page of "Extreme horticulture"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          |        | ✓      | ✓           | ✓     |
      | Publish content                             |        | ✓      | ✓           | ✓     |
      | Update own published content                |        | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          |        | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as a member of the "Extreme horticulture" solution
    When I go to the homepage of the "Extreme horticulture" solution
    # Can not propose or publish content.
    Then I should not see the link "Add discussion"
    And I should not see the link "Add document"
    And I should not see the link "Add event"
    And I should not see the link "Add news"
    Given event content:
      | title            | state | solution             | author           |
      | 'Sity and Guilds | draft | Extreme horticulture | Mustrum Ridcully |
    When I go to the content page of the type event with the title "'Sity and Guilds"
    Then I should get an access denied error


    # Solution. Content creation: any user. Moderated.
    Given I am on the members page of "Prehumous morbid bibliomancy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                                 | Member | Author | Facilitator | Owner |
      | View published content                                     | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users                  |        |        | ✓           | ✓     |
      | Start a discussion                                         | ✓      | ✓      | ✓           | ✓     |
      | Propose content for publication, pending approval          | ✓      |        |             |       |
      | Approve proposed content for publication                   |        |        | ✓           | ✓     |
      | Publish content without approval                           |        | ✓      | ✓           | ✓     |
      | Propose changes to own published content, pending approval | ✓      |        |             |       |
      | Approve proposed changes to published content              |        |        | ✓           | ✓     |
      | Update own published content without approval              |        | ✓      | ✓           | ✓     |
      | Update any content                                         |        |        | ✓           | ✓     |
      | Request changes on content from other users                |        |        | ✓           | ✓     |
      | Request deletion of own content, pending approval          | ✓      |        |             |       |
      | Approve requested deletion of content                      |        |        | ✓           | ✓     |
      | Delete own content without approval                        |        | ✓      | ✓           | ✓     |
      | Delete any content                                         |        |        | ✓           | ✓     |
      | Manage users                                               |        |        | ✓           | ✓     |

    Given I am logged in as "Dr. John Hicks"
    When I go to the homepage of the "Prehumous morbid bibliomancy" solution
    # Can propose content but not publish.
    And I click "Add discussion"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add document"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add event"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    When I click "Add news"
    Then I should see the button "Propose"
    But I should not see the button "Publish"
    Given event content:
      | title             | state     | solution                     | author           |
      | Head of the River | validated | Prehumous morbid bibliomancy | Dr. John Hicks   |
      | Poore Boys Funne  | draft     | Prehumous morbid bibliomancy | Mustrum Ridcully |
    When I go to the edit form of the "Head of the River" event
    Then I should see the button "Request deletion"
    And I should see the button "Propose changes"
    But I should not see the link "Delete"
    And I should not see the button "Update"
    When I go to the content page of the type event with the title "Poore Boys Funne"
    Then I should get an access denied error

    # Solution. Content creation: any user. Non-moderated.
    Given I am on the members page of "Posthumous morbid bibliomancy"
    When I click "Member permissions"
    Then the "member permissions" table should be:
      | Permission                                  | Member | Author | Facilitator | Owner |
      | View published content                      | ✓      | ✓      | ✓           | ✓     |
      | View unpublished content from other users   |        |        | ✓           | ✓     |
      | Start a discussion                          | ✓      | ✓      | ✓           | ✓     |
      | Publish content                             | ✓      | ✓      | ✓           | ✓     |
      | Update own published content                | ✓      | ✓      | ✓           | ✓     |
      | Update any content                          |        |        | ✓           | ✓     |
      | Request changes on content from other users |        |        | ✓           | ✓     |
      | Delete own content                          | ✓      | ✓      | ✓           | ✓     |
      | Delete any content                          |        |        | ✓           | ✓     |
      | Manage users                                |        |        | ✓           | ✓     |

    Given I am logged in as "Hex"
    When I go to the homepage of the "Posthumous morbid bibliomancy" solution
    # Can publish content but not propose.
    And I click "Add discussion"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add document"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add event"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    When I click "Add news"
    Then I should see the button "Publish"
    But I should not see the button "Propose"
    Given event content:
      | title                         | state     | collection                    | author           |
      | May Morning                   | validated | Posthumous morbid bibliomancy | Hex              |
      | A veritable heyhoe-rumbledown | draft     | Posthumous morbid bibliomancy | Mustrum Ridcully |
    When I go to the edit form of the "May Morning" event
    Then I should see the link "Delete"
    And I should see the button "Update"
    But I should not see the button "Request deletion"
    And I should not see the button "Propose changes"
    When I go to the content page of the type event with the title "A veritable heyhoe-rumbledown"
    Then I should get an access denied error

  # The permissions table should not be accessible for non-public groups.
  Scenario: Access the membership permissions information table
    Given the following collections:
      | title                | state     |
      | Valid Bibliomancy    | validated |
      | Draft Bibliomancy    | draft     |
      | Proposed Bibliomancy | proposed  |
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
