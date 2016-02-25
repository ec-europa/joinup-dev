Feature: User authentication
  In order to protect the integrity of the website
  I want to make sure proper permissions are given to appropriate roles

  Background:
    Given collections:
      | name                        | author          | uri               |
      | Überwaldean Land Eels       | Arnold Sideways | http://drupal.org |

  Scenario: Anonymous user can see the user login page and a collection homepage
    Given I am not logged in
    When I visit "user"
    Then I should see the text "Log in"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    But I should not see the text "Log out"
    And I should not see the text "My account"

  @api
  Scenario Outline: Anonymous user should be able to view created collections
    Given I am not logged in
    Then I visit "<path>"

    Examples:
      | path                           |
      | rdf_entity/http%3A\\drupal.org |

  Scenario Outline: Anonymous user cannot access restricted pages
    Given I am not logged in
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |
      | rdf_entity/add/collection      |

  @api
  Scenario Outline: Authenticated user should inherit anonymous user's permissions but also be able to view collections
    Given I am logged in as a user with the "authenticated" role
    Then I visit "<path>"

    Examples:
      | path                           |
      | rdf_entity/http%3A\\drupal.org |

  @api
  Scenario Outline: Authenticated user cannot access site administration
    Given I am logged in as a user with the "authenticated" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |
      | rdf_entity/add/collection      |

  @api
  Scenario Outline: User an access these paths
    Given I am logged in as a user with the "user" role
    Then I visit "<path>"

    Examples:
      | path                           |
      | rdf_entity/http%3A\\drupal.org |
      | rdf_entity/add/collection      |

  @api
  Scenario Outline: User cannot access these paths
    Given I am logged in as a user with the "user" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |

  @api
  Scenario Outline: Moderator can access these paths
    Given I am logged in as a user with the "moderator" role
    Then I visit "<path>"

    Examples:
      | path                           |
      | rdf_entity/http%3A\\drupal.org |
      | admin/people                   |
      | admin/content/rdf              |

  @api
  Scenario Outline: Moderator cannot access these paths
    Given I am logged in as a user with the "moderator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/structure                |
      | rdf_entity/add/collection      |

  @api
  Scenario Outline: Administrator can access these paths
    Given I am logged in as a user with the "administrator" role
    Then I visit "<path>"

    Examples:
      | path                           |
      | rdf_entity/http%3A\\drupal.org |

  @api
  Scenario Outline: Administrator role cannot access these paths
    Given I am logged in as a user with the "administrator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                           |
      | admin                          |
      | admin/config                   |
      | admin/content                  |
      | admin/people                   |
      | admin/structure                |
      | admin/content/rdf              |
      | rdf_entity/add/collection      |
