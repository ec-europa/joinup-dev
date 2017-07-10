Feature: User authentication
  In order to protect the integrity of the website
  As a product owner
  I want to make sure users with various roles can only access pages they are authorized to

  Scenario: Anonymous user can see the user login page
    Given I am not logged in
    When I visit "user"
    Then I should see the text "Log in"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    But I should not see the text "Log out"
    And I should not see the text "My account"

  Scenario Outline: Anonymous user can access public pages
    Given I am not logged in
    Then I visit "<path>"

    Examples:
      | path          |
      | collections   |
      | user/login    |
      | user/password |
      | user/register |

  Scenario Outline: Anonymous user cannot access restricted pages
    Given I am not logged in
    When I go to "<path>"
    Then I should see the error message "Access denied. You must log in to view this page."

    Examples:
      | path                               |
      | admin                              |
      | admin/config                       |
      | admin/content                      |
      | admin/content/rdf                  |
      | admin/people                       |
      | admin/structure                    |
      | admin/structure/views              |
      | propose/collection                 |
      | propose/solution                   |
      | dashboard                          |
      | node                               |
      | node/add                           |
      | node/add/custom_page               |
      | node/add/discussion                |
      | node/add/document                  |
      | node/add/event                     |
      | node/add/news                      |
      | rdf_entity/add                     |
      | rdf_entity/add/asset_distribution  |
      | rdf_entity/add/asset_release       |
      | rdf_entity/add/collection          |
      | rdf_entity/add/contact_information |
      | rdf_entity/add/licence             |
      | rdf_entity/add/owner               |
      | rdf_entity/add/solution            |
      | licence                            |

  @api
  Scenario Outline: Authenticated user can access pages they are authorized to
    Given I am logged in as a user with the "authenticated" role
    Then I visit "<path>"

    Examples:
      | path               |
      | propose/collection |
      | collections        |
      | dashboard          |
      | user               |

  @api
  Scenario Outline: Authenticated user cannot access site administration
    Given I am logged in as a user with the "authenticated" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                               |
      | admin                              |
      | admin/config                       |
      | admin/content                      |
      | admin/content/rdf                  |
      | admin/people                       |
      | admin/structure                    |
      | admin/structure/views              |
      | propose/solution                   |
      | licence                            |
      | licence/add                        |
      | node                               |
      | node/add                           |
      | node/add/custom_page               |
      | node/add/discussion                |
      | node/add/document                  |
      | node/add/event                     |
      | node/add/news                      |
      | rdf_entity/add                     |
      | rdf_entity/add/asset_distribution  |
      | rdf_entity/add/asset_release       |
      | rdf_entity/add/collection          |
      | rdf_entity/add/contact_information |
      | rdf_entity/add/licence             |
      | rdf_entity/add/owner               |
      | rdf_entity/add/solution            |

  @api
  Scenario Outline: Moderator can access pages they are authorized to
    Given I am logged in as a user with the "moderator" role
    Then I visit "<path>"

    Examples:
      | path               |
      | admin/people       |
      | admin/content/rdf  |
      | dashboard          |
      | licence            |
      | licence/add        |
      | propose/collection |

  @api
  Scenario Outline: Moderator cannot access restricted pages
    Given I am logged in as a user with the "moderator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                               |
      | admin                              |
      | admin/config                       |
      | admin/content                      |
      | admin/structure                    |
      | admin/structure/views              |
      | propose/solution                   |
      | node                               |
      | node/add                           |
      | node/add/custom_page               |
      | node/add/discussion                |
      | node/add/document                  |
      | node/add/event                     |
      | node/add/news                      |
      | rdf_entity/add                     |
      | rdf_entity/add/asset_distribution  |
      | rdf_entity/add/asset_release       |
      | rdf_entity/add/collection          |
      | rdf_entity/add/contact_information |
      | rdf_entity/add/licence             |
      | rdf_entity/add/owner               |
      | rdf_entity/add/solution            |

  @api
  Scenario Outline: Administrator can access pages they are authorized to
    Given I am logged in as a user with the "administrator" role
    Then I visit "<path>"

    Examples:
      | path        |
      | collections |
      | dashboard   |

  @api
  Scenario Outline: Administrator cannot access pages intended for site building and development
    Given I am logged in as a user with the "administrator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                               |
      | admin                              |
      | admin/config                       |
      | admin/content                      |
      | admin/content/rdf                  |
      | admin/people                       |
      | admin/structure                    |
      | node                               |
      | node/add                           |
      | node/add/custom_page               |
      | node/add/discussion                |
      | node/add/document                  |
      | node/add/event                     |
      | node/add/news                      |
      | rdf_entity/add                     |
      | rdf_entity/add/asset_distribution  |
      | rdf_entity/add/asset_release       |
      | rdf_entity/add/collection          |
      | rdf_entity/add/contact_information |
      | rdf_entity/add/licence             |
      | rdf_entity/add/owner               |
      | rdf_entity/add/solution            |
