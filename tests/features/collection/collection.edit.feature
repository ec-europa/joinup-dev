@api @group-a
Feature: Editing communities
  In order to manage communities
  As a community owner or community facilitator
  I need to be able to edit communities through the UI

  @terms
  Scenario: Edit a community
    Given the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    And the following contact:
      | name  | Community editorial             |
      | email | community.editorial@example.com |
    And community:
      | title                 | logo     | banner     | abstract                                   | access url                             | closed | creation date    | description                               | content creation         | moderation | topic             | owner                | contact information  | state |
      | Überwaldean Land Eels | logo.png | banner.jpg | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified. | facilitators and authors | yes        | Supplier exchange | Organisation example | Community editorial | draft |
    When I am logged in as a facilitator of the "Überwaldean Land Eels" community
    And I go to the homepage of the "Überwaldean Land Eels" community
    And I click "Edit" in the "Entity actions" region
    Then I should see the heading "Edit Community Überwaldean Land Eels"
    Then the following fields should be present "Title, Description, Abstract, Topic, Geographical coverage, Keywords, Content creation, Moderated, Motivation"
    And the following field widgets should be present "Contact information, Owner"
    And the following fields should not be present "Langcode, Translation, Affiliates, Enable the search field, Query presets, Limit"
    And I should see "Short description text of the community. Appears on the Overview page. (Leave blank to use the trimmed value of the Description field.)"
    And I should see "Add a country name relevant to the content of this community."
    And the "Description" wysiwyg editor should have the buttons "HTML block format, Bold, Italic, Remove format, Link, Unlink, Bullet list, Numbered list, Outdent, Indent, Blockquote, Image, File, Table, Video Embed, Cut, Copy, Paste, Paste Text, Undo, Redo, Source code"
    And the "Abstract" wysiwyg editor should have the buttons "HTML block format, Bold, Italic, Remove format, Link, Unlink, Outdent, Indent, Source code"

    # Query builder is disabled in communities.
    But I should not see the button "Add and configure filter"

    # Quick check to see that we can edit a field.
    When I fill in "Title" with "Überwaldean Sea Eels"
    And I press the "Save as draft" button
    Then I should see the heading "Überwaldean Sea Eels"
