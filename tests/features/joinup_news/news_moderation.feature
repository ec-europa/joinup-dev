@api @group-g
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
      | Username      | E-mail                            | Roles     |
      | Batman        | adminOfWayneINC@example.com       | moderator |
      | Superman      | dailyPlanetEmployee23@example.com |           |
      | Hawkgirl      | hawkSounds@example.com            |           |
      | Eagle         | WolrdWarVeteran@example.com       |           |
      | Question      | WhoAmI@example.com                |           |
      | Vandal Savage | voldemort@example.com             |           |
      | Cheetah       | ihatewonderwoman@example.com      |           |
      | Mirror Master | mirrormirroronthewall@example.com |           |
      | Metallo       | kryptoniteEverywhere@example.com  |           |
    Given collections:
      | title          | moderation | state     | content creation |
      | Justice League | no         | validated | members          |
      | Legion of Doom | yes        | validated | members          |
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
      | title                         | headline                                    | body                                                                    | state            | author        | collection     |
      | Creating Justice League       | 6 Members to start with                     | TBD                                                                     | draft            | Eagle         | Justice League |
      | Hawkgirl is a spy             | Her race lies in another part of the galaxy | Hawkgirl has been giving information about Earth to Thanagarians.       | proposed         | Eagle         | Justice League |
      | Hawkgirl helped Green Lantern | Hawkgirl went against Thanagarians?         | It was all of a sudden when Hawkgirl turned her back to her own people. | validated        | Eagle         | Justice League |
      | Space cannon fired            | Justice League fired at army facilities     | Justice league is now the enemy                                         | needs_update     | Eagle         | Justice League |
      | Eagle to join in season 4     | Will not start before S04E05                | The offer came when I helped defeating Iphestus armor.                  | proposed         | Eagle         | Justice League |
      | Question joined JL            | Justice league took in Question             | The famous detective is now part of JL.                                 | draft            | Question      | Justice League |
      | Creating Legion of Doom       | 7 Members to start with                     | We need equal number of members with the JL.                            | draft            | Mirror Master | Legion of Doom |
      | Stealing from Batman          | Hide in his car's mirror                    | I need to steal from Batman.                                            | validated        | Mirror Master | Legion of Doom |
      | Learn batman's secret         | Can I find batman's secret identity         | I have the opportunity to find out his identity.                        | proposed         | Mirror Master | Legion of Doom |
      | Stealing complete             | All data were copied                        | Now someone has to decrypt the data.                                    | needs_update     | Mirror Master | Legion of Doom |
      | Kill the sun                  | Savages plan                                | As it turns out Savage's plan is to cause a solar storm.                | deletion_request | Mirror Master | Legion of Doom |

  Scenario: Draft state doesn't change when facilitator edits news.
    Given I am logged in as "Eagle"
    When I go to the "Creating Justice League" news
    And I click "Edit"
    And I press "Save"
    Then the "Creating Justice League" news content should have the "draft" state

  Scenario Outline: Members, facilitators and owners can see and add news.
    Given I am logged in as "<user>"
    And I go to the homepage of the "<title>" collection
    Then I should see the link "Add news"
    When I click "Add news"
    Then I should see the heading "Add news"
    And the following buttons should be present "<available buttons>"
    And the following buttons should not be present "<unavailable buttons>"
    Examples:
      | user          | title          | available buttons               | unavailable buttons                                 |
      # Post-moderated collection, member
      | Eagle         | Justice League | Save as draft, Publish          | Propose, Request changes, Request deletion, Preview |
      # Post-moderated collection, facilitator
      | Hawkgirl      | Justice League | Save as draft, Publish          | Propose, Request changes, Request deletion, Preview |
      # Post-moderated collection, owner
      | Superman      | Justice League | Save as draft, Publish          | Propose, Request changes, Request deletion, Preview |
      # Pre-moderated collection, member
      | Mirror Master | Legion of Doom | Save as draft, Propose          | Publish, Request changes, Request deletion, Preview |
      # Pre-moderated collection, facilitator
      | Metallo       | Legion of Doom | Save as draft, Publish, Propose | Request changes, Request deletion, Preview          |
      # Pre-moderated collection, owner
      | Vandal Savage | Legion of Doom | Save as draft, Publish, Propose | Request changes, Request deletion, Preview          |

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

  @terms
  Scenario: Add news as a member to a post-moderated collection.
    # Add news as a member.
    # There is no need to check for a facilitator because when he creates news,
    # he does it as a member. The transitions are the same for post moderated
    # collections in terms of news creation.
    When I am logged in as "Eagle"
    And I go to the homepage of the "Justice League" collection
    And I click "Add news"
    Then I should see the heading "Add news"
    And the following fields should be present "Headline, Short title, Logo, Content, Topic, Keywords, Geographical coverage"
    # The entity is new, so the current workflow state should not be shown.
    And the following fields should not be present "Current workflow state"

    # The sections about managing revisions and groups should not be visible.
    And I should not see the text "Revision information"
    And the following fields should not be present "Groups audience, State, Other groups, Create new revision, Revision log message"

    And the following buttons should be present "Save as draft, Publish"
    And the following buttons should not be present "Propose, Request changes, Request deletion, Preview"
    When I fill in the following:
      | Short title | Eagle joins the JL                   |
      | Headline    | Eagle from WWII                      |
      | Content     | Specialized in close combat training |
    And I select "Employment and Support Allowance" from "Topic"
    And I press "Save as draft"
    # Check reference to news page.
    Then I should see the success message 'News Eagle joins the JL has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And the "Eagle joins the JL" news content should not be published
    # Test a transition change.
    When I go to the "Eagle joins the JL" news
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Save as draft, Publish"
    And the following buttons should not be present "Propose, Request changes, Request deletion, Preview"
    And the following fields should be present "Motivation"
    And I press "Publish"
    Then I should see the success message "News Eagle joins the JL has been updated."
    And the "Eagle joins the JL" news content should be published
    When I click "Justice League"
    Then I should see the link "Eagle joins the JL"

  @terms
  Scenario: Add news as a member to a pre-moderated collection and get it validated by a facilitator.
    # Add news as a member.
    When I am logged in as "Cheetah"
    And I go to the homepage of the "Legion of Doom" collection
    And I click "Add news"
    And the following buttons should be present "Save as draft, Propose"
    And the following buttons should not be present "Publish, Request changes, Request deletion, Preview"
    When I fill in the following:
      | Short title | Cheetah kills WonderWoman                             |
      | Headline    | Scarch of poison                                      |
      | Content     | A specific poison could expose Wonder-womans weakness |
    And I select "Supplier exchange" from "Topic"
    And I press "Propose"
    # Check reference to news page.
    # Todo: Why should we not see a success message after creating a news article? See ISAICP-2761
    Then I should not see the success message "News <em>Cheetah kills WonderWoman</em> has been created."
    And I should see the heading "Cheetah kills WonderWoman"
    And the "Cheetah kills WonderWoman" news content should not be published
    And I should see the text "Collection"
    And I should see the text "Legion of Doom"
    When I go to the "Cheetah kills WonderWoman" news
    Then I should see the link "Edit"
    # Edit and publish the news as a facilitator
    When I am logged in as "Metallo"
    When I go to the "Cheetah kills WonderWoman" news
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "Update, Publish"
    And the following buttons should not be present "Save as draft, Request changes, Request deletion, Preview"
    And I press "Publish"
    And the "Cheetah kills WonderWoman" news content should be published
    When I click "Legion of Doom"
    Then I should see the link "Cheetah kills WonderWoman"

  Scenario Outline: Members can only edit news they own for specific states.
    Given I am logged in as "<user>"
    And I go to the "<title>" news
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "<available buttons>"
    And the following buttons should not be present "<unavailable buttons>"
    Examples:
      | user          | title                         | available buttons               | unavailable buttons                                 |
      # Post-moderated. State: draft, owned by Eagle
      | Eagle         | Creating Justice League       | Save as draft, Publish          | Propose, Request changes, Preview                   |
      # Pre-moderated. State: draft, can propose
      | Mirror Master | Creating Legion of Doom       | Save as draft, Propose          | Publish, Request changes, Request deletion, Preview |
      # Post-moderated. State: validated, owned by Eagle who is a normal member. Should only be able to create a new draft.
      | Eagle         | Hawkgirl helped Green Lantern | Save new draft, Update          | Publish, Request changes, Preview                   |
      # Pre-moderated. State: validated, owned by Mirror Master who is a normal member. Should only be able to create a new draft and propose changes.
      | Mirror Master | Stealing from Batman          | Save new draft, Propose changes | Update, Publish, Preview                            |

  Scenario Outline: Members cannot edit news they own for specific states.
    Given I am logged in as "<user>"
    And I go to the "<title>" news
    Then I should not see the link "Edit"
    Examples:
      | user          | title                   |
      # State: needs update
      # Todo: rejected content should still be editable. Ilias suggests it should then move to Draft state. See ISAICP-2761.
      | Question      | Space cannon fired      |
      # State: draft, not owned
      | Eagle         | Question joined JL      |
      # State: draft, not owned
      | Cheetah       | Creating Legion of Doom |
      # State: needs update, not owned
      # Todo: rejected content should still be editable. Ilias suggests it should then move to Draft state. See ISAICP-2761.
      | Cheetah       | Stealing complete       |
      # State: deletion request, owned
      | Mirror Master | Kill the sun            |

  Scenario Outline: Facilitators have access to all content except from draft content without published version.
    Given I am logged in as "<user>"
    And I go to the "<title>" news
    Then I should see the link "Edit"
    When I click "Edit"
    Then I should not see the heading "Access denied"
    And the following buttons should be present "<available buttons>"
    And the following buttons should not be present "<unavailable buttons>"
    And I log out

    Examples:
      | user     | title                         | available buttons                       | unavailable buttons                                                |
      # Post moderated
      # News article in 'proposed' state.
      | Hawkgirl | Hawkgirl is a spy             | Update, Publish                         | Save as draft, Request changes, Request deletion, Preview          |
      # Published content can be moved back to 'Proposed', 'Draft' or to 'Needs update' state by a facilitator. It can also be updated.
      | Hawkgirl | Hawkgirl helped Green Lantern | Save new draft, Update, Request changes | Save as draft, Propose, Publish, Request deletion, Preview         |
      | Hawkgirl | Space cannon fired            | Propose                                 | Save as draft, Publish, Request changes, Request deletion, Preview |
      # Pre moderated
      # Published content can be moved back to 'Proposed' or 'Draft' state by a facilitator. It can also be updated.
      | Metallo  | Stealing from Batman          | Save new draft, Request changes, Update | Propose, Request deletion, Preview                                 |
      | Metallo  | Stealing complete             | Propose                                 | Save as draft, Request deletion, Preview                           |
      | Metallo  | Kill the sun                  | Reject deletion                         | Save as draft, Propose, Request changes, Request deletion, Preview |

  Scenario Outline: Facilitators cannot view unpublished content of another collection.
    Given I am logged in as "<user>"
    And I go to the "<title>" news
    Then I should see the heading "Access denied"
    Examples:
      | user     | title                   |
      # Post moderated
      | Metallo  | Creating Justice League |
      # Pre moderated
      | Hawkgirl | Creating Legion of Doom |

  Scenario Outline: Moderators can edit news regardless of their state.
    Given I am logged in as "Batman"
    And I go to the "<title>" news
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

  Scenario: An entity should be automatically published according to state
    When I am logged in as "Hawkgirl"
    And I go to the "Hawkgirl is a spy" news
    Then the "Hawkgirl is a spy" "news" content should not be published
    And the "Hawkgirl is a spy" "news" content should have 1 revision
    When I click "Edit"
    And I press "Publish"
    Then I should see the success message "News Hawkgirl is a spy has been updated."
    Then the "Hawkgirl is a spy" "news" content should be published
    And the "Hawkgirl is a spy" "news" content should have 2 revisions
    And I should see the link "Edit"
    When I click "Edit"
    And for "Short title" I enter "Hawkgirl saves the planet"
    And I fill in "Motivation" with "Let's change the short title."
    And I press "Request changes"
    Then I should see the success message "News Hawkgirl saves the planet has been updated."
    # A new draft has been created with a new title. The previously validated
    # revision (with the original title) should still be published.
    But I should see the heading "Hawkgirl is a spy"
    And the "Hawkgirl is a spy" "news" content should have 3 revisions
    # Finally, validate the proposed change. This should again create a new
    # revision, and the revision with the new title should become published.
    When I click "Edit"
    And I press "Publish"
    Then I should see the success message "News Hawkgirl saves the planet has been updated."
    And I should see the heading "Hawkgirl saves the planet"
    And the "Hawkgirl saves the planet" "news" content should have 4 revisions

  @terms
  Scenario: Check message draft url when click in Title.
    When I am logged in as "Eagle"
    And I go to the homepage of the "Justice League" collection
    And I click "Add news"
    Then I should see the heading "Add news"
    When I fill in the following:
      | Short title | Whale joins the JL                   |
      | Headline    | Whale from WWII                      |
      | Content     | Specialized in close combat training |
    And I select "Employment and Support Allowance" from "Topic"
    And I press "Save as draft"
    Then I should see the success message 'News Whale joins the JL has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I click "Whale joins the JL"
    Then I should see the text "Specialized in close combat training"

  @terms
  Scenario: Check news when click in My account page.
    When I am logged in as "Eagle"
    And I go to the homepage of the "Justice League" collection
    And I click "Add news"
    Then I should see the heading "Add news"
    When I fill in the following:
      | Short title | Eagle joins the CE                   |
      | Headline    | Eagle from WWVI                      |
      | Content     | Specialized in close combat training |
    And I select "Employment and Support Allowance" from "Topic"
    And I press "Save as draft"
    Then I should see the success message 'News Eagle joins the CE has been created as draft. You can find it in the section "My unpublished content" located in your My account page, or in the aforementioned section under the Collection it was created in.'
    And I click "My account page"
    Then I should see the heading "Eagle joins the CE"
