@api
  # This features tests the workflow transitions. This is a complete test.
  # There is already a 'proof of concept'. @see tests/features/joinup_news/add_news.collection.feature.
Feature: News moderation.
  As a facilitator, member or collection administrator, or a site administrator
  In order to manage collection news
  I need to be able to have a workflow based news management system.

  Scenario: Edit button should be shown only to the appropriate roles.
    # The complete list of the authorized roles are stored in configuration.
    # @see: modules/custom/joinup_news/config/install/joinup_news.settings.yml.
    Given collections:
      | title          | logo     | moderation |
      | Justice League | logo.png | no         |
      | Legion of Doom | logo.png | yes        |
    And users:
      | name          | pass             | mail                              | roles     |
      | Batman        | BatsEverywhere   | adminOfWayneINC@example.com       | moderator |
      | Superman      | PutYourGlassesOn | dailyPlanetEmployee23@example.com |           |
      | Hawkgirl      | IHaveWings       | hawkSounds@example.com            |           |
      | Eagle         | Ilovemycostume   | WolrdWarVeteran@example.com       |           |
      | Vandal Savage | IliveForever     | voldemort@example.com             |           |
      | Cheetah       | meowmeow         | ihatewonderwoman@example.com      |           |
      | Metallo       | checkMyHeart     | kryptoniteEverywhere@example.com  |           |
    And the following user memberships:
      | collection     | user          | roles         |
      | Justice League | Superman      | administrator |
      | Justice League | Hawkgirl      | facilitator   |
      | Justice League | Eagle         | member        |
      | Legion of Doom | Vandal Savage | administrator |
      | Legion of Doom | Metallo       | facilitator   |
      | Legion of Doom | Cheetah       | member        |
    And "news" content:
      | title                         | Kicker                                      | Content                                                                 | State         |
      | Creating Justice League       | 6 Members to start with                     | TBD                                                                     | Draft         |
      | Hawkgirl is a spy             | Her race lies in another part of the galaxy | Hawkgirl has been giving information about Earth to Thanagarians.       | Proposed      |
      | Hawkgirl helped Green Lantern | Hawkgirl went against Thanagarians?         | It was all of a sudden when Hawkgirl turned her back to her own people. | Validated     |
      | Space cannon fired            | Justice League fired at army facilities     | Justice league is now the enemy                                         | In Assessment |
      | Creating Legion of Doom       | 7 Members to start with                     | We need equal number of members with the JL.                            | Validated     |
    And "news" content belong to the corresponding collections:
      | content                       | collection     |
      | Creating Justice League       | Justice League |
      | Hawkgirl is a spy             | Justice League |
      | Hawkgirl helped Green Lantern | Justice League |
      | Space cannon fired            | Justice League |
      | Creating Legion of Doom       | Legion of Doom |

    # Hawkgirl is a spy
    When I am logged in as "Batman"
    And I visit the "news" content "Hawkgirl is a spy" edit screen
    Then I should get an access denied error