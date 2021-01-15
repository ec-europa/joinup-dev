@api
Feature: SPDX Permissions
  As a moderator of the website
  In order to better present the Joinup licences
  I need to control over the way the SPDX licences are shown.

  Scenario: Do not allow access to the canonical route of the SPDX licences.
    Given SPDX licences:
      | title             |
      | SPDX licence test |
    When I visit the "SPDX licence test" SPDX licence
    Then I should see the heading "Sign in to continue"
