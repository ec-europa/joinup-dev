@api @eupl
Feature:
  As the owner of the EUPL community
  in order to make it easier for users to find appropriate licences
  I need to be able to present them in a nice searchable way.

  Background:
    Given SPDX licences:
      | uri                       | title            | ID       |
      | http://joinup.eu/spdx/foo | SPDX licence foo | SPDX_FOO |
      | http://joinup.eu/spdx/bar | SPDX licence bar | SPDX_BAR |
    And licences:
      | uri                             | title          | description                             | type | spdx licence     | legal type                                                            |
      | http://joinup.eu/licence/foo    | Foo Licence    | Licence details for the foo licence.    |      | SPDX licence foo | Strong Community, Royalty free, Modify, Governments/EU, Use/reproduce |
      | http://joinup.eu/licence/bar    | Bar Licence    | Licence details for the bar licence.    |      | SPDX licence bar | Distribute                                                            |

  @javascript
  Scenario: Present and search the licences.
    Given licences:
      | uri                             | title          | description                             | type | spdx licence     | legal type                                                            |
      | http://joinup.eu/licence/random | Random Licence | A licence that should not be available. |      |                  | Distribute                                                            |

    When I am not logged in
    And I visit the "JLA" custom page
    Then I should see the heading "JLA"
    And I should see the link "licence SPDX identifier"
    And I should see the following filter categories in the correct order:
      | Can        |
      | Must       |
      | Cannot     |
      | Compatible |
      | Law        |
      | Support    |
    And I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"
    But I should not see the text "Random Licence"
    # Assert concatenated categories.
    And I should see the text "Strong Community, Governments/EU"

    And the licence item with the "SPDX_FOO" SPDX tag should include the following legal type categories:
      | Can     |
      | Must    |
      | Cannot  |
      | Support |
    And the response should contain "http://joinup.eu/spdx/foo.html#licenseText"
    And the response should contain "http://joinup.eu/spdx/bar.html#licenseText"

    When I click "Distribute" in the "Content" region
    Then I should see the text "1 licences found"
    And I should see the text "Bar Licence"
    But I should not see the text "Foo Licence"

    # Clicking again, deselects the item.
    When I click "Distribute" in the "Content" region
    Then I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"

    When I fill in "SPDX id" with "SPDX_FOO"
    Then I should see the text "1 licences found"
    And I should see the text "Foo Licence"
    But I should not see the text "Bar Licence"
    # Hitting 'Enter' does not trigger anything.
    When I hit enter in the keyboard on the field "SPDX id"
    Then I should see the text "1 licences found"
    And I should see the text "Foo Licence"
    But I should not see the text "Bar Licence"

    When I clear the content of the field "SPDX id"
    Then I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"

  Scenario: Test the licence comparer.

    Given SPDX licences:
      | uri                         | title              | ID         |
      | http://joinup.eu/spdx/baz   | SPDX licence baz   | SPDX_BAZ   |
      | http://joinup.eu/spdx/qux   | SPDX licence qux   | SPDX_QUX   |
      | http://joinup.eu/spdx/quux  | SPDX licence quux  | SPDX_QUUX  |
      | http://joinup.eu/spdx/waldo | SPDX licence waldo | SPDX_WALDO |

    # Test the page when the comparision list is missed.
    When I am on "/licence/compare"
    Then I should get a 404 HTTP response

    # Test the page when there's only one licence.
    When I am on "/licence/compare/SPDX_FOO"
    Then I should get a 404 HTTP response

    # Test the page when there are too many licences.
    When I am on "/licence/compare/SPDX_FOO/SPDX_BAR/SPDX_BAZ/SPDX_QUX/SPDX_QUUX/SPDX_WALDO"
    Then I should get a 404 HTTP response

    # Test the page when there are invalid SPDX IDs licences.
    When I am on "/licence/compare/ARBITRARY/SPDX/LICENCES"
    Then I should get a 404 HTTP response

    # Test the page with SPDX IDs without a corresponding Joinup licence.
    When I am on "/licence/compare/SPDX_FOO/SPDX_BAR/SPDX_BAZ/SPDX_QUX/SPDX_QUUX"
    Then I should get a 404 HTTP response

    When I visit "/licence/compare/SPDX_FOO/SPDX_BAR"
    Then I should see the "licence comparer" table
    And the "licence comparer" table should be:
      | Can                | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Use/reproduce      | x          |          |  |  |  |
      | Distribute         |            | x        |  |  |  |
      | Modify/merge       |            |          |  |  |  |
      | Sublicense         |            |          |  |  |  |
      | Commercial use     |            |          |  |  |  |
      | Use patents        |            |          |  |  |  |
      | Place warranty     |            |          |  |  |  |
      | Must               | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Incl. Copyright    |            |          |  |  |  |
      | Royalty free       | x          |          |  |  |  |
      | State changes      |            |          |  |  |  |
      | Disclose source    |            |          |  |  |  |
      | Copyleft/Share a.  |            |          |  |  |  |
      | Lesser copyleft    |            |          |  |  |  |
      | SaaS/network       |            |          |  |  |  |
      | Include licence    |            |          |  |  |  |
      | Rename modifs.     |            |          |  |  |  |
      | Cannot             | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Hold liable        |            |          |  |  |  |
      | Use trademark      |            |          |  |  |  |
      | Commerce           |            |          |  |  |  |
      | Modify             | x          |          |  |  |  |
      | Ethical clauses    |            |          |  |  |  |
      | Pub sector only    |            |          |  |  |  |
      | Sublicence         |            |          |  |  |  |
      | Compatible         | SPDX_FOO   | SPDX_BAR |  |  |  |
      | None N/A           |            |          |  |  |  |
      | Permissive         |            |          |  |  |  |
      | GPL                |            |          |  |  |  |
      | Other copyleft     |            |          |  |  |  |
      | Linking freedom    |            |          |  |  |  |
      | Multilingual       |            |          |  |  |  |
      | For data           |            |          |  |  |  |
      | For software       |            |          |  |  |  |
      | Law                | SPDX_FOO   | SPDX_BAR |  |  |  |
      | EU/MS law          |            |          |  |  |  |
      | US law             |            |          |  |  |  |
      | Licensor's law     |            |          |  |  |  |
      | Other law          |            |          |  |  |  |
      | Not fixed/local    |            |          |  |  |  |
      | Venue fixed        |            |          |  |  |  |
      | Support            | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Strong Community   | x          |          |  |  |  |
      | Governments/EU     | x          |          |  |  |  |
      | OSI approved       |            |          |  |  |  |
      | FSF Free/Libre     |            |          |  |  |  |
    And the page should not be cached

    When I reload the page
    Then the page should be cached

    Given I am logged in as a "licence_manager"
    And I am on the homepage
    When I click "Dashboard"
    And I click "Licences overview"
    And I click "Foo Licence"
    And I click "Edit"

    # Test cache tags invalidation.
    When I fill in "Title" with "Foo Licence changed"
    And I select "Attribution" from "Type"
    And I additionally select "Distribute" from "Licence legal type"
    When I press "Save"
    Then I should see the heading "Foo Licence changed"
    Given I am an anonymous user
    When I visit "/licence/compare/SPDX_FOO/SPDX_BAR"
    Then the page should not be cached

    # Test that SPDX_FOO "Can Distribute".
    And the "licence comparer" table should be:
      | Can                | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Use/reproduce      | x          |          |  |  |  |
      | Distribute         | x          | x        |  |  |  |
      | Modify/merge       |            |          |  |  |  |
      | Sublicense         |            |          |  |  |  |
      | Commercial use     |            |          |  |  |  |
      | Use patents        |            |          |  |  |  |
      | Place warranty     |            |          |  |  |  |
      | Must               | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Incl. Copyright    |            |          |  |  |  |
      | Royalty free       | x          |          |  |  |  |
      | State changes      |            |          |  |  |  |
      | Disclose source    |            |          |  |  |  |
      | Copyleft/Share a.  |            |          |  |  |  |
      | Lesser copyleft    |            |          |  |  |  |
      | SaaS/network       |            |          |  |  |  |
      | Include licence    |            |          |  |  |  |
      | Rename modifs.     |            |          |  |  |  |
      | Cannot             | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Hold liable        |            |          |  |  |  |
      | Use trademark      |            |          |  |  |  |
      | Commerce           |            |          |  |  |  |
      | Modify             | x          |          |  |  |  |
      | Ethical clauses    |            |          |  |  |  |
      | Pub sector only    |            |          |  |  |  |
      | Sublicence         |            |          |  |  |  |
      | Compatible         | SPDX_FOO   | SPDX_BAR |  |  |  |
      | None N/A           |            |          |  |  |  |
      | Permissive         |            |          |  |  |  |
      | GPL                |            |          |  |  |  |
      | Other copyleft     |            |          |  |  |  |
      | Linking freedom    |            |          |  |  |  |
      | Multilingual       |            |          |  |  |  |
      | For data           |            |          |  |  |  |
      | For software       |            |          |  |  |  |
      | Law                | SPDX_FOO   | SPDX_BAR |  |  |  |
      | EU/MS law          |            |          |  |  |  |
      | US law             |            |          |  |  |  |
      | Licensor's law     |            |          |  |  |  |
      | Other law          |            |          |  |  |  |
      | Not fixed/local    |            |          |  |  |  |
      | Venue fixed        |            |          |  |  |  |
      | Support            | SPDX_FOO   | SPDX_BAR |  |  |  |
      | Strong Community   | x          |          |  |  |  |
      | Governments/EU     | x          |          |  |  |  |
      | OSI approved       |            |          |  |  |  |
      | FSF Free/Libre     |            |          |  |  |  |
