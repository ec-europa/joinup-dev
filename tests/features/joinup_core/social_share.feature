@api @group-b
Feature: Sharing content on social networks
  As a user of the platform
  I want to share content in my social networks
  So that useful information has more visibility

  Scenario Outline: Sharing content on Facebook and Twitter.
    Given the following collection:
      | title | Social networks |
      | state | validated       |
    And <content type> content:
      | title                 | collection      | state     |
      | Important information | Social networks | validated |

    When I am an anonymous user
    And I go to the content page of the type "<content type>" with the title "Important information"
    And I click "Share"
    Then I should see the heading "Share Important information on"
    And I should see the link "Facebook"
    And the share link "Facebook" should point to the "Important information" content
    And I should see the link "Twitter"
    And the share link "Twitter" should point to the "Important information" content
    And I should see the link "Linkedin"
    And the share link "Linkedin" should point to the "Important information" content

    Examples:
      | content type |
      | event        |
      | document     |
      | discussion   |
      | news         |

  Scenario: Sharing solutions on social media.
    Given the following collection:
      | title | Social networks |
      | state | validated       |
    And solutions:
      | title              | collection      | state     |
      | Important solution | Social networks | validated |

    When I am logged in as a user with the "authenticated" role
    And I go to "/solutions"
    And I click the contextual link "Share" in the "Important solution" tile

    And I should see the link "Facebook"
    And the share link "Facebook" should point to the "Important solution" rdf entity
    And I should see the link "Twitter"
    And the share link "Twitter" should point to the "Important solution" rdf entity
    And I should see the link "Linkedin"
    And the share link "Linkedin" should point to the "Important solution" rdf entity
