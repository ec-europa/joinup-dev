@api
Feature:
  In order to make it easy to browse the latest discussions
  As a collection facilitator
  I want to have a page showing an overview of discussions

  Background:
    Given the following collections:
      | title      | logo     | banner     |
      | Nintendo64 | logo.png | banner.jpg |
      | Emulators  | logo.png | banner.jpg |
    And discussion content:
      | title               | collection | content                                     |
      | 20 year anniversary | Nintendo64 | The console was released in September 1996. |
      | NEC VR4300 CPU      | Emulators  | Designed by MTI for embedded applications.  |
    # @todo to be removed depending on ISAICP-2797
    And the discussions page:
      | title      | Discussions |
      | collection | Nintendo64  |
      | body       | Sample text |

    Scenario: Create an overview of discussions in a collection
      Given I am an anonymous user
      When I go to the homepage of the "Nintendo64" collection
      And I click "Discussions" in the "Left sidebar" region
      Then I should see the heading "Discussions"
      And I should see the "20 year anniversary" tile
      # I should not see the discussions of another collection.
      But I should not see the "NEC VR4300 CPU" tile
