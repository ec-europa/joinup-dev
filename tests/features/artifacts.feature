# @todo This test should be removed after QA. It's here just to help with QA.
@api
Feature: Test creation and upload of artifacts to S3 bucket.

  Scenario: Take a screenshot during test.

    Given I am on the homepage
    Then I should see the text "This text does not exist."
    Then I take a screenshot
