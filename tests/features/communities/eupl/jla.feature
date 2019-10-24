@api @eupl
Feature:
  As the owner of the EUPL community
  in order to make it easier for users to find appropriate licences
  I need to be able to present them in a nice searchable way.

  @javascript
  Scenario: Present and search the licences.
    Given SPDX licences:
      | uri                       | title            | ID       |
      | http://joinup.eu/spdx/foo | SPDX licence foo | SPDX_FOO |
      | http://joinup.eu/spdx/bar | SPDX licence bar | SPDX_BAR |
    And licences:
      | uri                             | title          | description                             | type | spdx licence     | legal type                                                            |
      | http://joinup.eu/licence/foo    | Foo Licence    | Licence details for the foo licence.    |      | SPDX licence foo | Strong Community, Royalty free, Modify, Governments/EU, Use/reproduce |
      | http://joinup.eu/licence/bar    | Bar Licence    | Licence details for the bar licence.    |      | SPDX licence bar | Distribute                                                            |
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
      | uri                              | title      | ID         |
      | http://joinup.eu/spdx/Apache-2.0 | Apache-2.0 | Apache-2.0 |
      | http://joinup.eu/spdx/GPL-2.0+   | GPL-2.0+   | GPL-2.0+   |
      | http://joinup.eu/spdx/BSL-1.0    | BSL-1.0    | BSL-1.0    |
      | http://joinup.eu/spdx/0BSD       | 0BSD       | 0BSD       |
      | http://joinup.eu/spdx/UPL-1.0    | UPL-1.0    | UPL-1.0    |
      | http://joinup.eu/spdx/LGPL-2.1   | LGPL-2.1   | LGPL-2.1   |
    And licences:
      | uri                               | title                                    | spdx licence | legal type                                                            |
      | http://joinup.eu/licence/apache20 | Apache License, Version 2.0              | Apache-2.0   | Strong Community, Royalty free, Modify, Governments/EU, Use/reproduce |
      | http://joinup.eu/licence/gpl2plus | GNU General Public License v2.0 or later | GPL-2.0+     | Distribute                                                            |

    # Test the page when the comparision list is missed.
    When I am on "/licence/compare"
    Then I should get a 404 HTTP response

    # Test the page when the parameter is not an array.
    When I am on "/licence/compare?licence=Apache-2.0"
    Then I should get a 404 HTTP response

    # Test the page when there's only one licence.
    When I am on "/licence/compare?licence[]=Apache-2.0"
    Then I should get a 404 HTTP response

    # Test the page when there are too many licences.
    When I am on "/licence/compare?licence[]=Apache-2.0&licence[]=GPL-2.0+&licence[]=BSL-1.0&licence[]=0BSD&licence[]=UPL-1.0&licence[]=LGPL-2.1"
    Then I should get a 404 HTTP response

    # Test the page when there are invalid characters in the SPDX licence ID.
    When I am on "/licence/compare?licence[]=Apache-2.0&licence[]=G$^#@!PL-2.0"
    Then I should get a 404 HTTP response

    # Test the page when there are invalid SPDX IDs licences.
    When I am on "/licence/compare?licence[]=Apache-2.0&licence[]=GPL-2.0+&licence[]=NOT-EXIST"
    Then I should get a 404 HTTP response

    # Test the page with SPDX IDs without a corresponding Joinup licence.
    When I am on "/licence/compare?licence[]=Apache-2.0&licence[]=GPL-2.0+&licence[]=BSL-1.0"
    Then I should get a 404 HTTP response

    When I visit "/licence/compare?licence[]=Apache-2.0&licence[]=GPL-2.0+"
    Then I should see the "licence comparer" table
    And the "licence comparer" table should be:
      | Can               | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Use/reproduce     | x          |          |  |  |  |
      | Distribute        |            | x        |  |  |  |
      | Modify/merge      |            |          |  |  |  |
      | Sublicense        |            |          |  |  |  |
      | Commercial use    |            |          |  |  |  |
      | Use patents       |            |          |  |  |  |
      | Place warranty    |            |          |  |  |  |
      | Must              | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Incl. Copyright   |            |          |  |  |  |
      | Royalty free      | x          |          |  |  |  |
      | State changes     |            |          |  |  |  |
      | Disclose source   |            |          |  |  |  |
      | Copyleft/Share a. |            |          |  |  |  |
      | Lesser copyleft   |            |          |  |  |  |
      | SaaS/network      |            |          |  |  |  |
      | Include licence   |            |          |  |  |  |
      | Rename modifs.    |            |          |  |  |  |
      | Cannot            | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Hold liable       |            |          |  |  |  |
      | Use trademark     |            |          |  |  |  |
      | Commerce          |            |          |  |  |  |
      | Modify            | x          |          |  |  |  |
      | Ethical clauses   |            |          |  |  |  |
      | Pub sector only   |            |          |  |  |  |
      | Sublicence        |            |          |  |  |  |
      | Compatible        | Apache-2.0 | GPL-2.0+ |  |  |  |
      | None N/A          |            |          |  |  |  |
      | Permissive        |            |          |  |  |  |
      | GPL               |            |          |  |  |  |
      | Other copyleft    |            |          |  |  |  |
      | Linking freedom   |            |          |  |  |  |
      | Multilingual      |            |          |  |  |  |
      | For data          |            |          |  |  |  |
      | For software      |            |          |  |  |  |
      | Law               | Apache-2.0 | GPL-2.0+ |  |  |  |
      | EU/MS law         |            |          |  |  |  |
      | US law            |            |          |  |  |  |
      | Licensor's law    |            |          |  |  |  |
      | Other law         |            |          |  |  |  |
      | Not fixed/local   |            |          |  |  |  |
      | Venue fixed       |            |          |  |  |  |
      | Support           | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Strong Community  | x          |          |  |  |  |
      | Governments/EU    | x          |          |  |  |  |
      | OSI approved      |            |          |  |  |  |
      | FSF Free/Libre    |            |          |  |  |  |
    And the page should not be cached

    When I reload the page
    Then the page should be cached

    Given I am logged in as a "licence_manager"
    And I am on the homepage
    When I click "Dashboard"
    And I click "Licences overview"
    And I click "Apache License, Version 2.0"
    And I click "Edit"

    # Test cache tags invalidation.
    When I fill in "Title" with "Apache License, Version 2.0 changed"
    And I fill in "Description" with "nothing"
    And I select "Attribution" from "Type"
    And I additionally select "Distribute" from "Licence legal type"
    When I press "Save"
    Then I should see the heading "Apache License, Version 2.0 changed"

    Given I am an anonymous user
    When I visit "/licence/compare?licence[]=Apache-2.0&licence[]=GPL-2.0+"
    Then the page should not be cached

    # Test that Apache-2.0 "Can Distribute".
    And the "licence comparer" table should be:
      | Can               | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Use/reproduce     | x          |          |  |  |  |
      | Distribute        | x          | x        |  |  |  |
      | Modify/merge      |            |          |  |  |  |
      | Sublicense        |            |          |  |  |  |
      | Commercial use    |            |          |  |  |  |
      | Use patents       |            |          |  |  |  |
      | Place warranty    |            |          |  |  |  |
      | Must              | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Incl. Copyright   |            |          |  |  |  |
      | Royalty free      | x          |          |  |  |  |
      | State changes     |            |          |  |  |  |
      | Disclose source   |            |          |  |  |  |
      | Copyleft/Share a. |            |          |  |  |  |
      | Lesser copyleft   |            |          |  |  |  |
      | SaaS/network      |            |          |  |  |  |
      | Include licence   |            |          |  |  |  |
      | Rename modifs.    |            |          |  |  |  |
      | Cannot            | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Hold liable       |            |          |  |  |  |
      | Use trademark     |            |          |  |  |  |
      | Commerce          |            |          |  |  |  |
      | Modify            | x          |          |  |  |  |
      | Ethical clauses   |            |          |  |  |  |
      | Pub sector only   |            |          |  |  |  |
      | Sublicence        |            |          |  |  |  |
      | Compatible        | Apache-2.0 | GPL-2.0+ |  |  |  |
      | None N/A          |            |          |  |  |  |
      | Permissive        |            |          |  |  |  |
      | GPL               |            |          |  |  |  |
      | Other copyleft    |            |          |  |  |  |
      | Linking freedom   |            |          |  |  |  |
      | Multilingual      |            |          |  |  |  |
      | For data          |            |          |  |  |  |
      | For software      |            |          |  |  |  |
      | Law               | Apache-2.0 | GPL-2.0+ |  |  |  |
      | EU/MS law         |            |          |  |  |  |
      | US law            |            |          |  |  |  |
      | Licensor's law    |            |          |  |  |  |
      | Other law         |            |          |  |  |  |
      | Not fixed/local   |            |          |  |  |  |
      | Venue fixed       |            |          |  |  |  |
      | Support           | Apache-2.0 | GPL-2.0+ |  |  |  |
      | Strong Community  | x          |          |  |  |  |
      | Governments/EU    | x          |          |  |  |  |
      | OSI approved      |            |          |  |  |  |
      | FSF Free/Libre    |            |          |  |  |  |
