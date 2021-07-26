@api @tallinn
Feature:
  - As a moderator I am able to set the access policy for dashboard data.
  - As a community member I'm able to access the dashboard data when the access
    policy is set to community.
  - As an anonymous or as a regular user I'm able to access the dashboard data
    only when the access policy is set to public.
  - As a moderator or as a Tallinn community facilitator I'm able to access the
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
    And the following community user membership:
      | community                      | user     | roles       |
      | Tallinn Ministerial Declaration | Monica   |             |
      | Tallinn Ministerial Declaration | Gilfoyle | facilitator |
    And tallinn_report content:
      | title | community                      |
      | Malta | Tallinn Ministerial Declaration |

    Given I am an anonymous user
    When I go to "/api/v1/collections/tallinn/report"
    Then I should get an access denied error
    When I go to "/admin/config/content/tallinn"
    Then I should see the heading "Sign in to continue"

    Given I am logged in as Dinesh
    When I go to "/api/v1/collections/tallinn/report"
    Then I should get an access denied error
    When I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Monica
    When I go to "/api/v1/collections/tallinn/report"
    Then I should get an access denied error
    When I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Gilfoyle
    When I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200
    When I go to "/admin/config/content/tallinn"
    Then I should get an access denied error

    Given I am logged in as Jared
    When I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200
    And the page should be cached
    When I go to "/admin/config/content/tallinn"
    Then the response status code should be 200
    And I should see the heading "Tallinn Settings"
    And the radio button "Restricted (moderators and Tallinn community facilitators)" from field "Access to Tallinn Ministerial Declaration data" should be selected

    # Make the dashboard data endpoint limited to community.
    Given I select the radio button "Community (moderators and Tallinn community members)"
    When I press "Save configuration"
    Then I should see the following success messages:
      | success messages                    |
      | Access policy successfully updated. |
    And the radio button "Community (moderators and Tallinn community members)" from field "Access to Tallinn Ministerial Declaration data" should be selected

    Given I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    # After changing the access policy, the cache has been cleared.
    And the page should not be cached
    When I reload the page
    Then the page should be cached

    # Edit the group entity.
    When I go to the edit form of the "Tallinn Ministerial Declaration" community
    And I fill in "Description" with "Hooli"
    When I press "Publish"
    And I go to "/api/v1/collections/tallinn/report"
    Then the page should not be cached
    When I reload the page
    Then the page should be cached

    # Edit any report.
    When I go to the edit form of the "Malta" "tallinn report"
    And I press "Save"
    When I go to "/api/v1/collections/tallinn/report"
    Then the page should not be cached
    When I reload the page
    Then the page should be cached

    Given I am logged in as Gilfoyle
    And I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Monica
    And I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Dinesh
    And I go to "/api/v1/collections/tallinn/report"
    Then I should get an access denied error

    Given I am an anonymous user
    And I go to "/api/v1/collections/tallinn/report"
    Then I should get an access denied error

    Given I am logged in as Jared
    When I go to "/admin/config/content/tallinn"
    Then the response status code should be 200
    And I should see the heading "Tallinn Settings"
    And the radio button "Community (moderators and Tallinn community members)" from field "Access to Tallinn Ministerial Declaration data" should be selected

    # Make the dashboard data endpoint public.
    Given I select the radio button "Public"
    When I press "Save configuration"
    Then I should see the following success messages:
      | success messages                    |
      | Access policy successfully updated. |
    And the radio button "Public" from field "Access to Tallinn Ministerial Declaration data" should be selected

    Given I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    # After changing the access policy, the cache has been cleared.
    And the page should not be cached
    When I reload the page
    Then the page should be cached

    Given I am logged in as Gilfoyle
    When I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    Given I am logged in as Dinesh
    When I go to "/api/v1/collections/tallinn/report"
    Then the response status code should be 200

    Given I am an anonymous user
    When I go to "/api/v1/collections/tallinn/report"
    # Due to a bug the report is not accessible for anonymous users.
    # See https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5509
    # Then the response status code should be 200
