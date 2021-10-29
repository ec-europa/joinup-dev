@api @group-e
Feature:
  As a site moderator/administrator
  When I'm logged in
  I want to be able to access the Joinup reporting section.

  Scenario Outline: Test the general access to Reporting section.
    Given I am logged in as a user with the <role> role
    When I am on "<url>"
    Then I should get a <code> HTTP response

    Examples:
      | url                                          | role          | code |
      | /admin/reporting                             | authenticated | 403  |
      | /admin/reporting                             | moderator     | 200  |
      | /admin/reporting/export-user-list            | authenticated | 403  |
      | /admin/reporting/export-user-list            | moderator     | 200  |
      | /admin/reporting/group-administrators/export | authenticated | 403  |
      | /admin/reporting/group-administrators/export | moderator     | 200  |
      | /admin/reporting/legal-notice-report         | authenticated | 403  |
      | /admin/reporting/legal-notice-report         | moderator     | 200  |
      | /admin/reporting/pipeline-log                | authenticated | 403  |
      | /admin/reporting/pipeline-log                | moderator     | 200  |
      | /admin/reporting/solutions-by-type           | authenticated | 403  |
      | /admin/reporting/solutions-by-type           | moderator     | 200  |
      | /admin/reporting/solutions-by-licences       | authenticated | 403  |
      | /admin/reporting/solutions-by-licences       | moderator     | 200  |

  Scenario: Links should be visible on the reporting page for a moderator.
    Given I am logged in as a user with the moderator role
    And I am on "/admin/reporting"
    Then I should see the following links:
      | Group administrators and facilitators |
      | Export user list                      |
      | Solutions by solution type            |
      | Solutions by licences                 |
      | Pipeline report                       |
      | Legal notice report                   |

  # This scenario is a light test to avoid regressions.
  Scenario: Moderators can access the list of published solutions and filter them by dates and type.
    Given the following collections:
      | title               | state     |
      | Monday's Artificial | validated |
      | Restless Burst      | validated |
    And solutions:
      | title           | collection          | state     | creation date    | modification date | solution type                                      |
      | Worthy Puppet   | Monday's Artificial | validated | 2003-01-31T23:00 | 2015-12-07T13:57  | Interoperability Specification, Networking Service |
      | Long Artificial | Restless Burst      | validated | 2012-09-14T00:00 | 2012-12-04T16:19  | Data Set Catalogue                                 |
      | Beta Frozen     | Restless Burst      | validated | 2017-10-15T14:54 | 2017-11-24T12:43  | e-Signature Creation Service                       |

    Given I am logged in as a moderator
    And I click "Reporting" in the "Administration toolbar" region
    And I click "Solutions by solution type"
    Then I should see the heading "Moderator: Solutions by type"
    And I should see the link "Worthy Puppet"
    And I should see the link "Long Artificial"
    And I should see the link "Beta Frozen"
    # Verify that the dates are shown in a human readable format.
    # @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4924
    And I should see the following lines of text:
      | Fri, 31/01/2003 - 23:00 |
      | Mon, 07/12/2015 - 13:57 |
      | Fri, 14/09/2012 - 00:00 |
      | Tue, 04/12/2012 - 16:19 |
      | Sun, 15/10/2017 - 14:54 |
      | Fri, 24/11/2017 - 12:43 |
    # Verify that the "Authored on" facet is in place.
    And I should see the link "January 2003"
    And I should see the link "September 2012"
    And I should see the link "October 2017"
    # Same for the "Changed" facet.
    And I should see the link "December 2012"
    And I should see the link "December 2015"
    And I should see the link "November 2017"
    # Same for the "Solution type" facet.
    And I should see the link "Interoperability Specification" in the "Left sidebar" region
    And I should see the link "Networking Service" in the "Left sidebar" region
    And I should see the link "Data Set Catalogue" in the "Left sidebar" region
    And I should see the link "e-Signature Creation Service" in the "Left sidebar" region
    # Verify that only solutions are shown.
    But I should not see the text "Monday's Artificial"
    And I should not see the text "Restless Burst"
    # Verify that the CSV link is present.
    # Note: the link is rendered as icon in a real browser.
    And I should see the link "Download CSV"
    When I click "Download CSV"
    Then I should get a valid web page

    # Verify that access to the CSV endpoint is forbidden for anonymous and normal users.
    When I am an anonymous user
    And I am on "/admin/reporting/solutions-by-type/csv?_format=csv"
    Then I should get an access denied error
    When I am logged in as an "authenticated user"
    And I am on "/admin/reporting/solutions-by-type/csv?_format=csv"
    Then I should get an access denied error
