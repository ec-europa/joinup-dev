@api
Feature:
  Tests related to the site verifications.

  Scenario: Google search verification.
    Given the following site verification:
      | Engine        | google                                 |
      | File          | google_abcdefg.html                    |
      | File contents | google-site-verification: google_abcde |
    When I go to "/google_abcdefg.html"
    Then I should see the text "google-site-verification: google_abcde"

