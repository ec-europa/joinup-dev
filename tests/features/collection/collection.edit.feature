@api
Feature: Editing collections
  In order to manage collections
  As a collection owner or collection facilitator
  I need to be able to edit collections through the UI

@terms
Scenario: Edit a collection
  Given the following owner:
    | name                 | type                    |
    | Organisation example | Non-Profit Organisation |
  Given collections:
    | title                 | logo     | banner     | abstract                                   | access url                             | closed | creation date    | description                               | elibrary creation | moderation | policy domain     | owner                | state |
    | Überwaldean Land Eels | logo.png | banner.jpg | Read up on all about <strong>dogs</strong> | http://dogtime.com/dog-breeds/profiles | yes    | 28-01-1995 12:05 | The Afghan Hound is elegance personified. | facilitators      | yes        | Supplier exchange | Organisation example | draft |
  Given I am logged in as a facilitator of the "Überwaldean Land Eels" collection
  When I go to the homepage of the "Überwaldean Land Eels" collection
  Then I should see the contextual link "Edit" in the Header region
  When I click the contextual link "Edit" in the Header region
  Then the following fields should be present "Title, Description, Abstract, Policy domain, Spatial coverage, Affiliates, Closed collection, eLibrary creation, Moderated"
  And the following field widgets should be present "Contact information, Owner"
  And I fill in "Title" with "Überwaldean Sea Eels"
  And I press the "Save as draft" button
  Then I should see the heading "Überwaldean Sea Eels"
