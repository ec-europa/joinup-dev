@api
Feature:
  - As a moderator, I am able to pre-upload images for logos & banners.

  Scenario Outline: Test moderator tasks.

    Given I am logged in as a moderator
    When I click "Library"
    Then I should see the heading "Media"

    When I click "Add media"
    Then I should see the heading "Add media item"

    When I click "<media type label>"
    Then I should see the heading "Add <media type label>"

    When I fill in "Name" with "Name of <media type label>"
    And I attach the file "library/<entity bundle>-<type>.<extension>" to "Image"
    And I press "Save"
    Then I should see the success message "<media type label> Name of <media type label> has been created."
    And I should see the link "Name of <media type label>"
    # Check if the paths were correctly configured.
    And the response should contain "/sites/default/files/styles/thumbnail/public/library/<entity bundle>/<type>/<entity bundle>-<type>.<extension>"

    # Can edit.
    When I click "Edit" in the "Name of <media type label>" row
    Then I should see the heading "Edit <media type label> Name of <media type label>"

    # Can delete.
    When I move backward one page
    And  I click "Delete" in the "Name of <media type label>" row
    Then I should see the heading "Are you sure you want to delete the media item Name of <media type label>?"
    When I press "Delete"
    Then I should see the success message "The media item Name of <media type label> has been deleted."

    Examples:
      | media type label  | entity bundle | type   | extension |
      | Collection banner | collection    | banner | jpg       |
      | Collection logo   | collection    | logo   | png       |
      | Solution banner   | solution      | banner | jpg       |
      | Solution logo     | solution      | logo   | png       |
      | News logo         | news          | logo   | png       |
      | Event logo        | event         | logo   | png       |
