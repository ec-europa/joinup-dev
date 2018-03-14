@api
Feature:
  As a site moderator/administrator
  When I'm logged in
  I want to be able to access the Joinup reporting section.

  Scenario Outline: Test the general access to Reporting section.
    Given I am logged in as a <role>
    And I am on the homepage
    When I am on "/admin/reporting"
    Then I should get a <code> HTTP response

    Examples:
      | role          | code |
      | authenticated | 403  |
      | administrator | 200  |
      | moderator     | 200  |

  # This scenario is a light test to avoid regressions.
  Scenario: Moderators can access the list of published solutions and filter them by dates and type.
    Given the following collections:
      | title               | state     |
      | Monday's Artificial | validated |
      | Restless Burst      | validated |
    And solutions:
      | title           | collection          | state     | creation date    | modification date | solution type                                                        |
      | Worthy Puppet   | Monday's Artificial | validated | 2003-01-31T23:00 | 2015-12-07T13:57  | [ABB162] Interoperability Specification, [ABB150] Networking Service |
      | Long Artificial | Restless Burst      | validated | 2012-09-14T00:00 | 2012-12-04T16:19  | [ABB24] Data Set Catalogue                                           |
      | Beta Frozen     | Restless Burst      | validated | 2017-10-15T14:54 | 2017-11-24T12:43  | [ABB55] e-Signature Creation Service                                 |

    Given I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Solutions by solution type"
    Then I should see the heading "Moderator: Solutions by type"
    And I should see the link "Worthy Puppet"
    And I should see the link "Long Artificial"
    And I should see the link "Beta Frozen"
    # Verify that the "Authored on" facet is in place.
    And I should see the link "January 2003"
    And I should see the link "September 2012"
    And I should see the link "October 2017"
    # Same for the "Changed" facet.
    And I should see the link "December 2012"
    And I should see the link "December 2015"
    And I should see the link "November 2017"
    # Same for the "Solution type" facet.
    And I should see the link "[ABB162] Interoperability Specification" in the "Left sidebar" region
    And I should see the link "[ABB150] Networking Service" in the "Left sidebar" region
    And I should see the link "[ABB24] Data Set Catalogue" in the "Left sidebar" region
    And I should see the link "[ABB55] e-Signature Creation Service" in the "Left sidebar" region
    # Verify that only solutions are shown.
    But I should not see the text "Monday's Artificial"
    And I should not see the text "Restless Burst"
    # Verify that the CSV link is present.
    # Note: the link is rendered as icon in a real browser.
    And I should see the link "Subscribe to Moderator: Solutions by type"
    When I click "Subscribe to Moderator: Solutions by type"
    Then I should get a valid web page

    # Verify that access to the CSV endpoint is forbidden for anonymous and normal users.
    When I am an anonymous user
    And I am on "/admin/reporting/solutions-by-type/csv?_format=csv"
    Then I should see the error message "Access denied. You must sign in to view this page."
    When I am logged in as an "authenticated user"
    And I am on "/admin/reporting/solutions-by-type/csv?_format=csv"
    Then I should get an access denied error
