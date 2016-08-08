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
  As a facilitator, member or collection administrator, or a site administrator
  In order to manage collection news
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
      | collection     | user          | roles         |
      | Justice League | Superman      | administrator |
      | Justice League | Hawkgirl      | facilitator   |
      | Justice League | Eagle         | member        |
      | Justice League | Question      | member        |
      | Legion of Doom | Vandal Savage | administrator |
      | Legion of Doom | Metallo       | facilitator   |
      | Legion of Doom | Mirror Master | member        |
      | Legion of Doom | Cheetah       | member        |
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

  Scenario Outline: Users and facilitators can see and add news.
    Given I am logged in as "<user>"
    And I go to the homepage of the "<title>" collection
    Then I should see the link "Add news"
    When I click "Add news"
    Then I should not see the heading "Access denied"
    And I should see the text "State"
    And the "State" field has the "<options available>" options
    And the "State" field does not have the "<options unavailable>" options
    Examples:
      | user          | title          | options available | options unavailable                              |
      # Post-moderated collection, member
      | Eagle         | Justice League | Draft, Validated  | Proposed, In assessment, Request deletion        |
      # Post-moderated collection, facilitator
      | Hawkgirl      | Justice League | Validated         | Draft, Proposed, In assessment, Request deletion |
      # Pre-moderated collection, member
      | Mirror Master | Legion of Doom | Draft, Propose    | Validate, In assessment, Request deletion        |
      # Pre-moderated collection, facilitator
      | Metallo       | Legion of Doom | Validated         | Draft, Propose, In assessment, Request deletion  |

  Scenario: Non-members and administrators cannot see the 'Add news' button.
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
    # Administrators cannot create content. Facilitators are the moderators of
    # the collection.
    When I am logged in as "Superman"
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
    # Administrators cannot create content. Facilitators are the moderators of
    # the collection.
    When I am logged in as "Superman"
    And I go to the homepage of the "Justice League" collection
    Then I should not see the link "Add news"
    When I am logged in as "Hawkgirl"
    And I go to the homepage of the "Justice League" collection
    Then I should see the link "Add news"
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
    And the following fields should be present "Headline, Kicker, Content, State"
    And the following fields should not be present "Groups audience"
    And the "field_news_state" field has the "Draft, Validated" options
    And the "field_news_state" field does not have the "Proposed, In assessment, Request deletion" options
    When I fill in the following:
      | Headline | Eagle joins the JL                   |
      | Kicker   | Eagle from WWII                      |
      | Content  | Specialized in close combat training |
    And I select "Draft" from "State"
    And I press "Save"
    # Check reference to news page.
    Then I should see the success message "News Eagle joins the JL has been created."
    And the "Eagle joins the JL" news content should not be published
    # Test a transition change.
    When I go to the "Eagle joins the JL" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "Validated" options
    And the "State" field does not have the "Proposed, In assessment, Request delection" options
    When I select "Validated" from "State"
    And I press "Save"
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
    And the "State" field has the "Draft, Proposed" options
    And the "State" field does not have the "Validated, In assessment, Request delection" options
    When I fill in the following:
      | Headline | Cheetah kills WonderWoman                             |
      | Kicker   | Scarch of poison                                      |
      | Content  | A specific poison could expose Wonder-womans weakness |
    And I select "Proposed" from "State"
    And I press "Save"
    # Check reference to news page.
    Then I should not see the success message "News <em>Cheetah kills WonderWoman</em> has been created."
    Then I should see the heading "Cheetah kills WonderWoman"
    And the "Cheetah kills WonderWoman" news content should not be published
    And I should see the text "Collection"
    And I should see the text "Legion of Doom"
    When I go to the "Cheetah kills WonderWoman" news page
    Then I should not see the link "Edit"
    # Edit and publish the news as a facilitator
    When I am logged in as "Metallo"
    When I go to the "Cheetah kills WonderWoman" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "Proposed, Validated" options
    And the "State" field does not have the "Draft, In assessment, Request delection" options
    When I select "Validated" from "State"
    And I press "Save"
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
    And the "State" field has the "<options available>" options
    And the "State" field does not have the "<options unavailable>" options
    Examples:
      | user          | title                         | options available | options unavailable                              |
      # State: draft, owned by Eagle
      | Eagle         | Creating Justice League       | Validated         | Draft, Proposed, In assessment                   |
      # State: validated, can report
      | Eagle         | Hawkgirl helped Green Lantern | In assessment     | Draft, Validated, Proposed                       |
      # State: draft, can propose
      | Mirror Master | Creating Legion of Doom       | Propose           | Draft, Validate, In assessment, Request deletion |
      # State: validated, can report
      | Mirror Master | Stealing from Batman          | In assessment     | Draft, Propose, Validate, Request deletion       |

  Scenario Outline: Members cannot edit news they own for specific states.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should not see the link "Edit"
    Examples:
      | user          | title                     |
      # State: proposed
      | Eagle         | Hawkgirl is a spy         |
      # State: in assessment
      | Eagle         | Space cannon fired        |
      # State: proposed
      | Eagle         | Eagle to join in season 4 |
      # State: draft, not owned
      | Eagle         | Question joined JL        |
      # State: proposed
      | Mirror Master | Learn batman's secret     |
      # State: draft, not owned
      | Cheetah       | Creating Legion of Doom   |
      # State: in assessment
      | Mirror Master | Stealing complete         |
      # State: deletion request
      | Mirror Master | Kill the sun              |

  Scenario Outline: Facilitators have access on content regardless of state.
    Given I am logged in as "<user>"
    And I go to the "<title>" news page
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the "State" field has the "<options available>" options
    And the "State" field does not have the "<options unavailable>" options
    Examples:
      | user     | title                         | options available   | options unavailable                               |
      # Post moderated
      | Hawkgirl | Hawkgirl is a spy             | Proposed, Validated | Draft, In assessment, Request deletion            |
      # Members can move to 'in assessment' state.
      | Hawkgirl | Hawkgirl helped Green Lantern | Validated, Proposed | Draft, Request deletion , In assessment           |
      | Hawkgirl | Space cannon fired            | Proposed            | Draft, Validated, In assessment, Request deletion |
      # Pre moderated
      # Facilitators have access to create news and directly put it to validate. For created and proposed, member role should be used.
      | Metallo  | Creating Legion of Doom       | Validated           | Draft, Proposed, In assessment, Request deletion  |
      # Members can move to 'in assessment' state.
      | Metallo  | Stealing from Batman          | Proposed, Validated | Draft, In assessment, Request deletion            |
      | Metallo  | Learn batman's secret         | Proposed, Validated | Draft, In assessment, Request deletion            |
      | Metallo  | Stealing complete             | Proposed            | Draft, Request deletion                           |
      | Metallo  | Kill the sun                  | Validated           | Draft, Proposed, In assessment, Request deletion  |

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
    And I select "Validated" from "State"
    And I press "Save"
    Then I should see the success message "News Hawkgirl is a spy has been updated."
    Then the "Hawkgirl is a spy" "news" content should be published
    And I should see the link "Edit"
    When I click "Edit"
    And I select "Proposed" from "State"
    And I press "Save"
    Then I should see the success message "News Hawkgirl is a spy has been updated."
    Then the "Hawkgirl is a spy" "news" content should not be published
