@api @group-a
Feature: Editing collections
  In order to manage collections
  As a collection owner or collection facilitator
  I need to be able to edit collections through the UI

  @terms
  Scenario: Edit a collection
    Given the following owner:
      | name                 | type                    |
      | Organisation example | Non-Profit Organisation |
    And the following contact:
      | name  | Collection editorial             |
      | email | collection.editorial@example.com |
    And collections:
      | title                 | logo     | banner     | abstract                                   | access url                             | closed | creation date    | description                               | content creation | moderation | policy domain     | owner                | contact information  | state |
      | Überwaldean Land Eels | logo.png | banner.jpg | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified. | facilitators     | yes        | Supplier exchange | Organisation example | Collection editorial | draft |
    When I am logged in as a facilitator of the "Überwaldean Land Eels" collection
    And I go to the homepage of the "Überwaldean Land Eels" collection
    And I click "Edit" in the "Entity actions" region
    Then the following fields should be present "Title, Description, Abstract, Policy domain, Geographical coverage, Keywords, Content creation, Moderated, Motivation"
    And the following field widgets should be present "Contact information, Owner"
    And the following fields should not be present "Langcode, Translation, Affiliates, Enable the search field, Query presets, Limit"
    And I should see "Short description text of the collection. Appears on the Overview page. (Leave blank to use the trimmed value of the Description field.)"
    And I should see "Add a country name relevant to the content of this collection."
    # Query builder is disabled in collections.
    And I should not see the button "Add and configure filter"
    When I fill in "Title" with "Überwaldean Sea Eels"
    And I press the "Save as draft" button
    Then I should see the heading "Überwaldean Sea Eels"

