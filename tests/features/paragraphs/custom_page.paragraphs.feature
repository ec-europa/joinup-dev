@api @group-d
Feature:
  As a site builder of the site
  In order to be able to better control the structure of my content
  I need to be able to place paragraphs for content.

  Background:
    Given the following collection:
      | title | Paragraphs collection |
      | state | validated             |

  @javascript
  Scenario: Paragraph sections are multivalue and sort-able.
    Given I am logged in as a facilitator of the "Paragraphs collection" collection
    And I go to the "Paragraphs collection" collection
    And I open the plus button menu
    And I click "Add custom page"
    And I fill in "Title" with "Paragraphs page"
    And I press "Save"
    Then I should see the heading "Paragraphs page"

    When I open the header local tasks menu
    And I click "Edit"
    # The first paragraphs item is open by default.
    Then there should be 1 paragraph in the "Custom page body" field
    Then I should see the "Remove" button in the "Custom page body" field for paragraph 1
    Given I press "Remove" in the "Custom page body" field for paragraph 1

    Then the page should contain no paragraphs

    Given I press "Add Simple paragraph" in the "Custom page body" paragraphs field
    Then there should be 1 paragraph in the "Custom page body" field

    Given I press "Add Simple paragraph" in the "Custom page body" paragraphs field
    Then there should be 2 paragraphs in the "Custom page body" field

    When I enter "AAAAAAAAAA" in the "Body" wysiwyg editor in the "Custom page body" field for paragraph 1
    And I enter "BBBBBBBBBB" in the "Body" wysiwyg editor in the "Custom page body" field for paragraph 2

    And I drag the table row at position 2 up
    And I press "Save"
    Then there should be 2 paragraphs in the page
    And I should see the following paragraphs in the given order:
      | BBBBBBBBBB |
      | AAAAAAAAAA |

  Scenario: Privileged users can add a map and/or an iframe and map paragraph.
    Given users:
      | Username |
      | Zohan    |
    And the following collection user membership:
      | collection            | user  |
      | Paragraphs collection | Zohan |
    And custom_page content:
      | title                     | body        | collection            | author |
      | Don't Mess with the Zohan | Wanna mess? | Paragraphs collection | Zohan  |

    # Normal members cannot add maps.
    When I am logged in as "Zohan"
    And I go to the edit form of the "Don't Mess with the Zohan" "custom page"
    Then I should see the button "Add Simple paragraph"
    And I should not see the button "Add Map"
    But I should not see the button "Add IFrame"

    # Facilitators can add maps.
    When I am logged in as a facilitator of the "Paragraphs collection" collection
    And I go to the edit form of the "Don't Mess with the Zohan" "custom page"
    Then I should see the button "Add Simple paragraph"
    And I should see the button "Add Map"
    But I should not see the button "Add IFrame"

    When I fill in "Body" with "I'm half Australian, half Mt. Everest"
    And I press "Save"
    Then I should see the success message "Custom page Don't Mess with the Zohan has been updated."
    And I should see "I'm half Australian, half Mt. Everest"

    # Moderators can add maps.
    Given I am logged in as a moderator
    And I go to the edit form of the "Don't Mess with the Zohan" "custom page"
    Then I should see the button "Add Simple paragraph"
    And I should see the button "Add Map"
    And I should see the button "Add IFrame"

    When I press "Add Map"
    # As the Webtools Map a webservice, we only test a fake JSON.
    And I fill in "JSON" with "{\"foo\":\"bar\"}"
    And I press "Save"
    Then I should see the success message "Custom page Don't Mess with the Zohan has been updated."
    And I should see "I'm half Australian, half Mt. Everest"
    And the response should contain "{\"foo\":\"bar\"}"

    When I go to the edit form of the "Don't Mess with the Zohan" "custom page"
    And I press "Add IFrame"
    And I fill in "Iframe URL" with "http://example.com"
    And I press "Save"
    # We only test that the <iframe .../> element is in page. Unfortunately, we
    # cannot do precision RegExp search in page source, so we only check for the
    # existence of the iframe and its URL.
    Then I should see 1 "iframe" elements
    And the response should contain "src=\"http://example.com\""

  @javascript
  Scenario Outline: Add an accordion to the custom page.
    Given I am logged in as a <role>
    And I go to the "Paragraphs collection" collection
    And I open the plus button menu
    And I click "Add custom page"
    And I fill in "Title" with "Paragraphs accordion page"
    Given I press "Remove" in the "Custom page body" field for paragraph 1
    Then the page should contain no paragraphs

    # The "List additional actions" is the button arrow that shows the full list of paragraphs to add.
    Given I press "List additional actions"
    Then I press "Add Accordion" in the "Custom page body" paragraphs field

    # 3 paragraphs are the default number when adding an accordion.
    # 1. the accordion wrapper, 2. accordion item and 3. the simple paragraph contained in the item.
    And there should be 3 paragraph in the "Custom page body" field
    When I press "Add Accordion item"
    # An accordion item and a simple paragraph have been added.
    Then there should be 5 paragraphs in the "Custom page body" field

    Given I fill in the 1st "Item label" with "Super" in the "Custom page body" field
    And I enter "Clumsy text" in the 1st "Body" wysiwyg editor in the "Custom page body" field
    And I fill in the 2nd "Item label" with "Duper" in the "Custom page body" field
    And I enter "Crunchy text" in the 2nd "Body" wysiwyg editor in the "Custom page body" field

    And I press "Save"
    Then I should see the heading "Paragraphs accordion page"
    And I should see the text "Super"
    And I should see the text "Duper"
    But I should not see the text "Clumsy text"
    And I should not see the text "Crunchy text"

    Given I am not logged in
    And I go to the "Paragraphs accordion page" custom page
    And I click "Duper"
    Then I should see the text "Crunchy text"
    But I should not see the text "Clumsy text"

    Given I click "Super"
    Then I should see the text "Clumsy text"
    But I should not see the text "Crunchy text"

    Examples:
      | role                                                  |
      | moderator                                             |
      | facilitator of the "Paragraphs collection" collection |
