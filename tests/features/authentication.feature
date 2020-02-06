@group-a
Feature: User authentication
  In order to protect the integrity of the website
  As a product owner
  I want to make sure users with various roles can only access pages they are authorized to

  Scenario Outline: Anonymous user can access public pages
    Given I am not logged in
    Then I visit "<path>"

    Examples:
      | path                |
      | collections         |
      | user/login          |
      | user/password       |
      | user/register       |

  Scenario Outline: Anonymous user cannot access restricted pages
    Given I am not logged in
    When I go to "<path>"
    Then I should see the heading "Sign in to continue"

    Examples:
      | path                                                           |
      | admin                                                          |
      | admin/config                                                   |
      | admin/config/search/redirect                                   |
      | admin/content                                                  |
      | admin/content/media                                            |
      | admin/content/rdf                                              |
      | admin/legal-notice                                             |
      | admin/legal-notice/add                                         |
      | admin/people                                                   |
      | admin/reporting/export-user-list                               |
      | admin/reporting/group-administrators/rdf_entity/collection     |
      | admin/reporting/group-administrators/rdf_entity/collection/csv |
      | admin/reporting/solutions-by-licences                          |
      | admin/reporting/solutions-by-type                              |
      | admin/structure                                                |
      | admin/structure/media                                          |
      | admin/structure/views                                          |
      | dashboard                                                      |
      | licence                                                        |
      | licence/add                                                    |
      | media/add                                                      |
      | media/add/collection_banner                                    |
      | media/add/collection_logo                                      |
      | media/add/solution_banner                                      |
      | media/add/solution_logo                                        |
      | media/add/event_logo                                           |
      | media/add/news_logo                                            |
      | node                                                           |
      | node/add                                                       |
      | node/add/custom_page                                           |
      | node/add/discussion                                            |
      | node/add/document                                              |
      | node/add/event                                                 |
      | node/add/news                                                  |
      | propose/collection                                             |
      | propose/solution                                               |
      | rdf_entity/add                                                 |
      | rdf_entity/add/asset_distribution                              |
      | rdf_entity/add/asset_release                                   |
      | rdf_entity/add/collection                                      |
      | rdf_entity/add/contact_information                             |
      | rdf_entity/add/licence                                         |
      | rdf_entity/add/owner                                           |
      | rdf_entity/add/solution                                        |
      | user/subscriptions                                             |

  @api
  Scenario Outline: Authenticated user can access pages they are authorized to
    Given I am logged in as a user with the "authenticated" role
    Then I visit "<path>"

    Examples:
      | path               |
      | collections        |
      | propose/collection |
      | user               |
      | user/subscriptions |

  @api
  Scenario Outline: Authenticated user cannot access site administration
    Given I am logged in as a user with the "authenticated" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                                                           |
      | admin                                                          |
      | admin/config                                                   |
      | admin/content                                                  |
      | admin/content/media                                            |
      | admin/content/rdf                                              |
      | admin/legal-notice                                             |
      | admin/legal-notice/add                                         |
      | admin/people                                                   |
      | admin/reporting/export-user-list                               |
      | admin/reporting/group-administrators/rdf_entity/collection     |
      | admin/reporting/group-administrators/rdf_entity/collection/csv |
      | admin/reporting/solutions-by-licences                          |
      | admin/reporting/solutions-by-type                              |
      | admin/structure                                                |
      | admin/structure/media                                          |
      | admin/structure/views                                          |
      | dashboard                                                      |
      | propose/solution                                               |
      | licence                                                        |
      | licence/add                                                    |
      | media/add                                                      |
      | media/add/collection_banner                                    |
      | media/add/collection_logo                                      |
      | media/add/solution_banner                                      |
      | media/add/solution_logo                                        |
      | media/add/event_logo                                           |
      | media/add/news_logo                                            |
      | node                                                           |
      | node/add                                                       |
      | node/add/custom_page                                           |
      | node/add/discussion                                            |
      | node/add/document                                              |
      | node/add/event                                                 |
      | node/add/news                                                  |
      | rdf_entity/add                                                 |
      | rdf_entity/add/asset_distribution                              |
      | rdf_entity/add/asset_release                                   |
      | rdf_entity/add/collection                                      |
      | rdf_entity/add/contact_information                             |
      | rdf_entity/add/licence                                         |
      | rdf_entity/add/owner                                           |
      | rdf_entity/add/solution                                        |

  @api
  Scenario Outline: Moderator can access pages they are authorized to
    Given I am logged in as a user with the "moderator" role
    Then I visit "<path>"

    Examples:
      | path                                                           |
      | admin/people                                                   |
      | admin/content/rdf                                              |
      | admin/content/media                                            |
      | admin/legal-notice                                             |
      | admin/legal-notice/add                                         |
      | admin/reporting/export-user-list                               |
      | admin/reporting/group-administrators/rdf_entity/collection     |
      | admin/reporting/group-administrators/rdf_entity/collection/csv |
      | admin/reporting/solutions-by-licences                          |
      | admin/reporting/solutions-by-type                              |
      | dashboard                                                      |
      | licence                                                        |
      | licence/add                                                    |
      | media/add                                                      |
      | media/add/collection_banner                                    |
      | media/add/collection_logo                                      |
      | media/add/solution_banner                                      |
      | media/add/solution_logo                                        |
      | media/add/event_logo                                           |
      | media/add/news_logo                                            |
      | propose/collection                                             |

  @api
  Scenario Outline: Moderator cannot access restricted pages
    Given I am logged in as a user with the "moderator" role
    When I go to "<path>"
    Then I should get an access denied error

    Examples:
      | path                               |
      | admin                              |
      | admin/config                       |
      | admin/config/search/redirect       |
      | admin/content                      |
      | admin/structure                    |
      | admin/structure/views              |
      | admin/structure/media              |
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
      | path                         |
      | admin/config/search/redirect |
      | collections                  |

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
      | admin/content/media                |
      | admin/content/rdf                  |
      | admin/legal-notice                 |
      | admin/legal-notice/add             |
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
