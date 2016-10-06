@api
  # This features tests the workflow transitions.
  #
  # The steps @Then I go to the "news" content :title edit screen
  # can be tested through the UI by going to the page of the content and press
  # the edit button. But there are many edit buttons on the screen including
  # the contextual links so there can be a false positive when searching for the button
  # or when pressing an 'Edit' button.
  #
  # The steps @And the :title news content should (not )be published
  # cannot be tested through the UI and are only for ensuring proper
  # functionality.
Feature: News moderation.
  As a member, facilitator or collection owner
  In order to manage news about my collection
  I need to be able to have a workflow based news management system.

  Background:
    # The complete permission matrix is stored in configuration.
    # @see: modules/custom/joinup_news/config/install/joinup_news.settings.yml.
    Given users:
      | name          | pass             | mail                              | roles     |
      | Batman        | BatsEverywhere   | adminOfWayneINC@example.com       | moderator |
      | Superman      | PutYourGlassesOn | dailyPlanetEmployee23@example.com |           |
      | Hawkgirl      | IHaveWings       | hawkSounds@example.com            |           |
      | Eagle         | Ilovemycostume   | WolrdWarVeteran@example.com       |           |
      | Question      | secretsSecrets   | WhoAmI@example.com                |           |
      | Vandal Savage | IliveForever     | voldemort@example.com             |           |
      | Cheetah       | meowmeow         | ihatewonderwoman@example.com      |           |
      | Mirror Master | hideinmirrors    | mirrormirroronthewall@example.com |           |
      | Metallo       | checkMyHeart     | kryptoniteEverywhere@example.com  |           |
    Given collections:
      | title          | moderation |
      | Justice League | no         |
      | Legion of Doom | yes        |
    And the following collection user memberships:
      | collection     | user          | roles       |
      | Justice League | Superman      | owner       |
      | Justice League | Hawkgirl      | facilitator |
      | Justice League | Eagle         | member      |
      | Justice League | Question      | member      |
      | Legion of Doom | Vandal Savage | owner       |
      | Legion of Doom | Metallo       | facilitator |
      | Legion of Doom | Mirror Master | member      |
      | Legion of Doom | Cheetah       | member      |
    And "news" content:
      | title                         | kicker                                      | body                                                                    | state            | author        |
      | Creating Justice League       | 6 Members to start with                     | TBD                                                                     | draft            | Eagle         |
      | Hawkgirl is a spy             | Her race lies in another part of the galaxy | Hawkgirl has been giving information about Earth to Thanagarians.       | proposed         | Eagle         |
      | Hawkgirl helped Green Lantern | Hawkgirl went against Thanagarians?         | It was all of a sudden when Hawkgirl turned her back to her own people. | validated        | Eagle         |
      | Space cannon fired            | Justice League fired at army facilities     | Justice league is now the enemy                                         | in_assessment    | Eagle         |
      | Eagle to join in season 4     | Will not start before S04E05                | The offer came when I helped defeating Iphestus armor.                  | proposed         | Eagle         |
      | Question joined JL            | Justice league took in Question             | The famous detective is now part of JL.                                 | draft            | Question      |
      | Creating Legion of Doom       | 7 Members to start with                     | We need equal number of members with the JL.                            | draft            | Mirror Master |
      | Stealing from Batman          | Hide in his car's mirror                    | I need to steal from Batman.                                            | validated        | Mirror Master |
      | Learn batman's secret         | Can I find batman's secret identity         | I have the opportunity to find out his identity.                        | proposed         | Mirror Master |
      | Stealing complete             | All data were copied                        | Now someone has to decrypt the data.                                    | in_assessment    | Mirror Master |
      | Kill the sun                  | Savages plan                                | As it turns out Savage's plan is to cause a solar storm.                | deletion_request | Mirror Master |
    And "news" content belong to the corresponding collections:
      | content                       | collection     |
      | Creating Justice League       | Justice League |
      | Hawkgirl is a spy             | Justice League |
      | Hawkgirl helped Green Lantern | Justice League |
      | Space cannon fired            | Justice League |
      | Eagle to join in season 4     | Justice League |
      | Question joined JL            | Justice League |
      | Creating Legion of Doom       | Legion of Doom |
      | Stealing from Batman          | Legion of Doom |
      | Learn batman's secret         | Legion of Doom |
      | Stealing complete             | Legion of Doom |
      | Kill the sun                  | Legion of Doom |

  Scenario: Draft state doesn't change when facilitator edits news.
    Given I am logged in as "Eagle"
    When I go to the "Creating Justice League" news page
    And I click "Edit"
    And I press "Save"
    Then the "Creating Justice League" news content should have the "draft" state

  Scenario Outline: Members, facilitators and owners can see and add news.
    Given I am logged in as "<user>"
    And I go to the homepage of the "<title>" collection
    Then I should see the link "Add news"
    When I click "Add news"
    Then I should see the heading "Add news"
    And the following buttons should be present "<buttons available>"
    And the following buttons should not be present "<buttons unavailable>"
    Examples:
      | user          | title          | buttons available                | buttons unavailable                |
      # Post-moderated collection, member
      | Eagle         | Justice League | Save as draft, Validate          | Propose, Report, Request deletion  |
      # Post-moderated collection, facilitator
      | Hawkgirl      | Justice League | Save as draft, Validate          | Propose, Report, Request deletion  |
      # Post-moderated collection, owner
      | Superman      | Justice League | Save as draft, Validate          | Propose, Report, Request deletion  |
      # Pre-moderated collection, member
      | Mirror Master | Legion of Doom | Save as draft, Propose           | Validate, Report, Request deletion |
      # Pre-moderated collection, facilitator
      | Metallo       | Legion of Doom | Save as draft, Validate, Propose | Report, Request deletion           |
      # Pre-moderated collection, owner
      | Vandal Savage | Legion of Doom | Save as draft, Validate, Propose | Report, Request deletion           |

  Scenario: Anonymous users and non-members cannot see the 'Add news' button.
    # Check visibility for anonymous users.
    When I am not logged in
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    # Check visibility for authenticated users.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    # User from another collection should not be able to see the 'Add news'.
    When I am logged in as "Cheetah"
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"

  Scenario: "Add news" button should only be shown to moderators and group members.
    # Check visibility for anonymous users.
    When I am not logged in
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    # Check visibility for authenticated users.
    When I am logged in as an "authenticated user"
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    # Site moderators should be able to add news.
    When I am logged in as "Batman"
    And I go to the homepage of the "Justice League" collection
    Then I should see the link "Add news"
    # User from another collection should not be able to see the 'Add news'.
    When I am logged in as "Cheetah"
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    # Owners should be able to add news.
    When I am logged in as "Superman"
    And I go to the homepage of the "Justice League" collection
    Then I should see the link "Add news"
    # Facilitators should be able to add news.
    When I am logged in as "Hawkgirl"
    And I go to the homepage of the "Justice League" collection
    Then I should see the link "Add news"
    # A normal member should be able to add news.
    When I am logged in as "Eagle"
    And I go to the homepage of the "Justice League" collection
    Then I should see the link "Add news"

  Scenario: Add news as a member to a post-moderated collection.
    # Add news as a member.
    # There is no need to check for a facilitator because when he creates news,
    # he does it as a member. The transitions are the same for post moderated
    # collections in terms of news creation.
    When I am logged in as "Eagle"
    And I go to the homepage of the "Justice League" collection
    And I click "Add news"
    Then I should see the heading "Add news"
    And the following fields should be present "Headline, Kicker, Content"
    And the following fields should not be present "Groups audience"
    And the following buttons should be present "Save as draft, Validate"
    And the following buttons should not be present "Propose, Report, Request deletion"
    When I fill in the following:
      | Headline | Eagle joins the JL                   |
      | Kicker   | Eagle from WWII                      |
      | Content  | Specialized in close combat training |
    And I press "Save as draft"
    # Check reference to news page.
    Then I should see the success message "News Eagle joins the JL has been created."
    And the "Eagle joins the JL" news content should not be published
    # Test a transition change.
    When I go to the "Eagle joins the JL" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Validate"
    And the following buttons should not be present "Propose, Report, Request deletion"
    And I press "Validate"
    Then I should see the text "Validated"
    Then I should see the success message "News Eagle joins the JL has been updated."
    And the "Eagle joins the JL" news content should be published
    When I click "Justice League"
    Then I should see the link "Eagle joins the JL"

  Scenario: Add news as a member to a pre-moderated collection and get it validated by a facilitator.
    # Add news as a member.
    When I am logged in as "Cheetah"
    And I go to the homepage of the "Legion of Doom" collection
    And I click "Add news"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Validate, Report, Request deletion"
    When I fill in the following:
      | Headline | Cheetah kills WonderWoman                             |
      | Kicker   | Scarch of poison                                      |
      | Content  | A specific poison could expose Wonder-womans weakness |
    And I press "Propose"
    # Check reference to news page.
    # Todo: Why should we not see a success message after creating a news article? See ISAICP-2761
    Then I should not see the success message "News <em>Cheetah kills WonderWoman</em> has been created."
    Then I should see the heading "Cheetah kills WonderWoman"
    And the "Cheetah kills WonderWoman" news content should not be published
    And I should see the text "Collection"
    And I should see the text "Legion of Doom"
    When I go to the "Cheetah kills WonderWoman" news page
    Then I should see the link "Edit"
    # Edit and publish the news as a facilitator
    When I am logged in as "Metallo"
    When I go to the "Cheetah kills WonderWoman" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Propose, Report, Validate"
    And the following buttons should not be present "Save as draft, Request deletion"
    And I press "Validate"
    Then I should see the text "Validated"
    And the "Cheetah kills WonderWoman" news content should be published
    When I click "Legion of Doom"
    Then I should see the link "Cheetah kills WonderWoman"

  Scenario Outline: Members can only edit news they own for specific states.
    # Post moderated.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "<buttons available>"
    And the following buttons should not be present "<buttons unavailable>"
    Examples:
      | user          | title                   | buttons available       | buttons unavailable                |
      # State: draft, owned by Eagle
      | Eagle         | Creating Justice League | Save as draft, Validate | Propose, Report                    |
      # State: draft, can propose
      | Mirror Master | Creating Legion of Doom | Save as draft, Propose  | Validate, Report, Request deletion |

  Scenario Outline: Members cannot edit news they own for specific states.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should not see the link "Edit"
    Examples:
      | user          | title                         |
      # State: in assessment
      # Todo: rejected content should still be editable. Ilias suggests it should then move to Draft state. See ISAICP-2761.
      | Eagle         | Space cannon fired            |
      # State: validated
      # Todo: validated content should still be editable, for as long as it can
      # does not stay in 'validated' state. See ISAICP-2761.
      | Eagle         | Hawkgirl helped Green Lantern |
      # State: draft, not owned
      | Eagle         | Question joined JL            |
      # State: draft, not owned
      | Cheetah       | Creating Legion of Doom       |
      # State: in assessment
      # Todo: rejected content should still be editable. Ilias suggests it should then move to Draft state. See ISAICP-2761.
      | Mirror Master | Stealing complete             |
      # State: validated
      # Todo: validated content should still be editable, for as long as it can
      # does not stay in 'validated' state. See ISAICP-2761.
      | Mirror Master | Stealing from Batman          |
      # State: deletion request
      | Mirror Master | Kill the sun                  |

  Scenario Outline: Facilitators have access on content regardless of state.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "<buttons available>"
    And the following buttons should not be present "<buttons unavailable>"
    Examples:
      | user     | title                         | buttons available                | buttons unavailable                               |
      # Post moderated
      | Hawkgirl | Hawkgirl is a spy             | Propose, Validate, Report        | Save as draft, Request deletion                   |
      # Members can move to 'in assessment' state.
      | Hawkgirl | Hawkgirl helped Green Lantern | Validate, Propose                | Save as draft, Report, Request deletion           |
      | Hawkgirl | Space cannon fired            | Propose                          | Save as draft, Validate, Report, Request deletion |
      # Pre moderated
      # Facilitators have access to create news and directly put it to validate. For created and proposed, member role should be used.
      | Metallo  | Creating Legion of Doom       | Save as draft, Propose, Validate | Report, Request deletion                          |
      # Validated content can be moved back to 'Proposed' state by a facilitator.Scenario:
      # @Todo: it should also be possible to move to 'Draft'. See ISAICP-2761
      | Metallo  | Stealing from Batman          | Propose, Validate                | Save as draft, Report, Request deletion           |
      # Members can move to 'in assessment' state.
      | Metallo  | Learn batman's secret         | Propose, Report, Validate        | Save as draft,  Request deletion                  |
      | Metallo  | Stealing complete             | Propose                          | Save as draft, Request deletion                   |
      | Metallo  | Kill the sun                  | Validate                         | Save as draft, Propose, Report, Request deletion  |

  Scenario Outline: Facilitators cannot view unpublished content of another collection.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should see the heading "Access denied"
    Examples:
      | user     | title                   |
      # Post moderated
      | Metallo  | Creating Justice League |
      # Pre moderated
      | Hawkgirl | Creating Legion of Doom |

  Scenario Outline: Moderators can edit news regardless of their state.
    Given I am logged in as "Batman"
    And I go to the "<title>" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    Examples:
      | title                         |
      | Creating Justice League       |
      | Hawkgirl is a spy             |
      | Hawkgirl helped Green Lantern |
      | Space cannon fired            |
      | Eagle to join in season 4     |
      | Question joined JL            |
      | Creating Legion of Doom       |
      | Creating Legion of Doom       |
      | Stealing from Batman          |
      | Learn batman's secret         |

  Scenario: An entity should be automatically published/un published according to state
    # Regardless of moderation, the entity is published for the states
    # Validated, In assessment, Request deletion
    # and unpublished for Draft and Proposed.
    When I am logged in as "Hawkgirl"
    And I go to the "Hawkgirl is a spy" news page
    Then I should see the link "Edit"
    When I click "Edit"
    And I press "Validate"
    Then I should see the success message "News Hawkgirl is a spy has been updated."
    Then the "Hawkgirl is a spy" "news" content should be published
    And I should see the link "Edit"
    When I click "Edit"
    And I press "Propose"
    Then I should see the success message "News Hawkgirl is a spy has been updated."
    Then the "Hawkgirl is a spy" "news" content should not be published
