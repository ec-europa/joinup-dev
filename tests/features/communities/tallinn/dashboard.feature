@api @tallinn
Feature:
  - As a moderator I am able to set the access policy for dashboard data.
  - As a collection member I'm able to access the dashboard data when the access
    policy is set to collection.
  - As an anonymous or as a regular user I'm able to access the dashboard data
    only when the access policy is set to public.
  - As a moderator or as a Tallinn collection facilitator I'm able to access the
    dashboard data regardless of the access policy.
  - Data returned by the dashboard endpoint is cached and the cache is
    invalidated when any of the report entities or the group entity are updated.

  Scenario: Access and cache test.

    Given users:
      | Username | Roles     |
      | Dinesh   |           |
      | Monica   |           |
      | Gilfoyle |           |
      | Jared    | moderator |
    And the following collection user membership:
      | collection                      | user     | roles       |
      | Tallinn Ministerial Declaration | Monica   |             |
      | Tallinn Ministerial Declaration | Gilfoyle | facilitator |
    And tallinn_report content:
      | title | collection                      |
      | Malta | Tallinn Ministerial Declaration |

    Given I am an anonymous user
    When I go to "/api/v1/communities/tallinn/report"
    Then I should see the following error message:
      | error messages                                     |
      | Access denied. You must sign in to view this page. |
    And I go to "/admin/config/content/tallinn"
    Then I should see the following error message:
      | error messages                                     |
      | Access denied. You must sign in to view this page. |

    Given I am logged in as Dinesh
    When I go to "/api/v1/communities/tallinn/report"
    Then I should get an access denied error
    And I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Monica
    When I go to "/api/v1/communities/tallinn/report"
    Then I should get an access denied error
    And I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Gilfoyle
    When I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200
    And I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Jared
    When I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200
    And the response should be cached
    When I go to "/admin/config/content/tallinn"
    Then the response status code should be 200
    And I should see the heading "Tallinn Settings"
    And the radio button "Restricted (moderators and Tallinn collection facilitators)" from field "Access to the dashboard data" should be selected

    # Make the dashboard data endpoint limited to collection.
    Given I select the radio button "Collection (moderators and Tallinn collection members)"
    When I press "Save configuration"
    Then I should see the following success messages:
      | success messages                    |
      | Access policy successfully updated. |
    And the radio button "Collection (moderators and Tallinn collection members)" from field "Access to the dashboard data" should be selected

    Given I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    # After changing the access policy, the cache has been cleared.
    And the response should not be cached
    But I reload the page
    Then the response should be cached

    # Edit the group entity.
    Given I go to the "Tallinn Ministerial Declaration" collection edit form
    And I fill in "Description" with "Hooli"
    When I press "Publish"
    And I go to "/api/v1/communities/tallinn/report"
    Then the response should not be cached
    But I reload the page
    Then the response should be cached

    # Edit any report.
    Given I go to the tallinn_report content "Malta" edit screen
    And I press "Save"
    When I go to "/api/v1/communities/tallinn/report"
    Then the response should not be cached
    But I reload the page
    Then the response should be cached

    Given I am logged in as Gilfoyle
    And I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Monica
    And I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Dinesh
    And I go to "/api/v1/communities/tallinn/report"
    Then I should get an access denied error

    Given I am an anonymous user
    And I go to "/api/v1/communities/tallinn/report"
    Then I should see the following error message:
      | error messages                                     |
      | Access denied. You must sign in to view this page. |

    Given I am logged in as Jared
    When I go to "/admin/config/content/tallinn"
    Then the response status code should be 200
    And I should see the heading "Tallinn Settings"
    And the radio button "Collection (moderators and Tallinn collection members)" from field "Access to the dashboard data" should be selected

    # Make the dashboard data endpoint public.
    Given I select the radio button "Public"
    When I press "Save configuration"
    Then I should see the following success messages:
      | success messages                    |
      | Access policy successfully updated. |
    And the radio button "Public" from field "Access to the dashboard data" should be selected

    Given I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    # After changing the access policy, the cache has been cleared.
    And the response should not be cached
    But I reload the page
    Then the response should be cached

    Given I am logged in as Gilfoyle
    And I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Dinesh
    And I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200

    Given I am an anonymous user
    And I go to "/api/v1/communities/tallinn/report"
    Then the response status code should be 200
