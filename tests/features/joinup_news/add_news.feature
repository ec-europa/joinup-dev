@api
Feature: Creation of news through the UI.
  In order to manage news
  As a user
  I need to be able to create news through the UI.

  Scenario: Share the news in other collections/solutions.
    Given user:
      | Username    | isotopedancer      |
      | First name  | Milana             |
      | Family name | Laninga            |
      | E-mail      | milana@example.com |
    And the following collections:
      | title            | description                                 | logo     | banner     | state     |
      | Metal fans       | "Share the love for nickel, tungsten & co." | logo.png | banner.jpg | validated |
      | Hardcore diggers | We dig up stuff hidden beneath the earth.   | logo.png | banner.jpg | validated |
      | Cool blacksmiths | Keeping it cool while working on hot stuff. | logo.png | banner.jpg | validated |
    And solutions:
      | title                     | description                               | logo     | banner     | state     |
      | Density catalogue project | Catalog density on metals with ease.      | logo.png | banner.jpg | validated |
      | Dig do's and don'ts       | How to dig up stuff with style.           | logo.png | banner.jpg | validated |
      | Anvil test routines       | How to determine reliability of the tool. | logo.png | banner.jpg | validated |
    And the following solution user membership:
      | solution                  | user          | roles       |
      | Density catalogue project | isotopedancer | facilitator |

    When I am logged in as a "facilitator" of the "Metal fans" collection
    And I go to the homepage of the "Metal fans" collection
    Then the following fields should not be present "Shared in, Motivation"

    # Log in as a facilitator of the "Density catalogue project" solution
    When I am logged in as isotopedancer
    And I go to the homepage of the "Density catalogue project" solution
    And I click "Add news" in the plus button menu

    # Check required fields.
    And I attach the file "test.zip" to "Add a new file"
    And I press "Upload"
    And I press "Publish"
    Then I should see the following lines of text:
      | Headline field is required.         |
      | Kicker field is required.           |
      | Content field is required.          |
      | File description field is required. |

    When I fill in the following:
      | Kicker           | Ytterbium was declared the ultimate metal of the year                                         |
      | Headline         | Strong request for this rare metal that is on the mouth of everybody                          |
      | Content          | Thanks to its lower density compared to thulium and lutetium its applications have increased. |
      | File description | Comparison materials                                                                          |

    # Reference a solution in the news.
    When I fill in "Referenced solution" with "Dig do's and don'ts"
    # Test that the title character limit is restricted. This cannot be reproduced in the normal UI but checks the form
    # validation.
    # @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3680
    And I press "Publish"
    Then I should see the error message "Kicker cannot be longer than 30 characters but is currently 53 characters long."
    When I fill in "Kicker" with "Ytterbium metal of the year"
    And I press "Publish"
    Then I should see the success message "News Ytterbium metal of the year has been created."
    # Verify that the referenced solution is rendered as tile.
    And I should see the "Dig do's and don'ts" tile
    # Check that the full author name is shown instead of the username.
    And I should see the link "Milana Laninga" in the "Content" region
    But I should not see the link "isotopedancer" in the "Content" region

    # Edit again and try to share into the same solution.
    When I click "Edit" in the "Entity actions" region
    And I fill in "Referenced solution" with values "Dig do's and don'ts, Dig do's and don'ts"
    And I press "Update"
    Then I should see the error message "The value Dig do's and don'ts is already selected for field Referenced solution."

    # Add another solution in the field.
    When I fill in "Referenced solution" with values "Dig do's and don'ts, Anvil test routines"
    And I press "Update"
    Then I should see the success message "News Ytterbium metal of the year has been updated."
    # Verify that the tiles are shown.
    Then I should see the "Dig do's and don'ts" tile
    And I should see the "Anvil test routines" tile
