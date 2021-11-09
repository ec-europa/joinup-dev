@api @group-a
Feature: Add distribution through the UI
  In order to manage distributions
  As a facilitator
  I need to be able to add "Distribution" RDF entities through the UI.

  Background:
    Given the following collection:
      | title      | Asset Distribution Test |
      | logo       | logo.png                |
      | state      | validated               |
     And the following solution:
      | title       | Solution random x name           |
      | collection  | Asset Distribution Test          |
      | description | Some reusable random description |
      | state       | validated                        |
    And the following release:
      | title         | 1.0.0 Authoritarian Alpaca |
      | description   | First public release.      |
      | is version of | Solution random x name     |
      | state         | validated                  |
    And the following licences:
      | title              | description                                              | deprecated |
      | WTFPL              | The WTFPL is a rather permissive licence                 | no         |
      | Deprecated licence | The deprecated licence should not be available to select | yes        |

  Scenario: Add a distribution to a solution as a facilitator.
    When I am logged in as a "facilitator" of the "Solution random x name" solution
    And I go to the homepage of the "Solution random x name" solution
    Then I should see the link "Add distribution"

    When I click "Add distribution"
    Then I should see the heading "Add Distribution"
    And the following fields should be present "Title, Description, Access URL, Licence, Format, Representation technique"
    But the following fields should not be present "Langcode, Translation"
    And the "Licence" field should contain the "WTFPL" options
    But the "Licence" field should not contain the "Deprecated licence" options

    When I press "Save"
    Then I should see the following error messages:
      | error messages             |
      | Title field is required.   |
      | Licence field is required. |

    And I should see the link "contacting us"
    When I fill in "Title" with "Linux x86-64 SDK"
    And I enter "<p>The full software development kit for systems based on the x86-64 architecture.</p>" in the "Description" wysiwyg editor
    And I upload the file "test.zip" to "Access URL"
    And I select "GNU zip" from "Format"
    And I select "Web Ontology Language Full/DL/Lite" from "Representation technique"
    And I select "WTFPL" from "Licence"
    And I press "Save"
    Then I should have 1 distribution
    And the "Linux x86-64 SDK" distribution should have the link of the "test.zip" in the access URL field

    # Check if the asset distribution is accessible.
    When I go to the homepage of the "Solution random x name" solution
    Then I should see the text "Distribution"
    And I should see the link "Linux x86-64 SDK"
    When I click "Linux x86-64 SDK"
    Then I should see the heading "Linux x86-64 SDK"
    And I should see the link "WTFPL"
    And I should see the text "The full software development kit for systems based on the x86-64 architecture."

    # The licence label should be shown also in the solution UI.
    When I go to the homepage of the "Solution random x name" solution
    Then I should see the text "WTFPL"
    # Clean up the asset distribution that was created through the UI.
    Then I delete the "Linux x86-64 SDK" asset distribution

  # Test that unauthorized users cannot add a distribution, both for a release
  # and directly from the solution page.
  Scenario: "Add distribution" button should not be shown to unprivileged users.
    Given I am logged in as a "facilitator" of the "Solution random x name" solution
    And I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    When I open the plus button menu
    Then I should see the link "Add distribution"

    Given I am logged in as a "member" of the "Asset Distribution Test" collection
    And I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    When I open the plus button menu
    Then I should not see the link "Add distribution"
    When I go to the homepage of the "Solution random x name" solution
    And I open the plus button menu
    Then I should not see the link "Add distribution"

    Given I am logged in as an "authenticated user"
    And I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    When I open the plus button menu
    Then I should not see the link "Add distribution"
    When I go to the homepage of the "Solution random x name" solution
    And I open the plus button menu
    Then I should not see the link "Add distribution"

    Given I am an anonymous user
    And I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    When I open the plus button menu
    Then I should not see the link "Add distribution"
    When I go to the homepage of the "Solution random x name" solution
    And I open the plus button menu
    Then I should not see the link "Add distribution"

  @uploadFiles:test.zip
  Scenario: Add a distribution to a release as a facilitator.
    When I am logged in as a "facilitator" of the "Solution random x name" solution
    When I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    And I click "Add distribution" in the plus button menu
    Then I should see the heading "Add Distribution"
    And the following fields should be present "Title, Description, Access URL, Licence, Format, Representation technique"
    And I should see the link "contacting us"
    When I fill in "Title" with "Source tarball"
    And I enter "<p>The full source code.</p>" in the "Description" wysiwyg editor
    Given I upload the file "test.zip" to "Access URL"
    And I select "WTFPL" from "Licence"
    And I select "Web Ontology Language Full/DL/Lite" from "Representation technique"
    And I press "Save"
    Then I should have 1 distribution

    # Debug step since the default view of the distribution, does not have the access URL shown.
    And the "Source tarball" distribution should have the link of the "test.zip" in the access URL field

    # Check if the asset distribution is accessible as an anonymous user
    When I am an anonymous user
    When I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    Then I should see the text "Distribution"
    And I should see the link "Source tarball"
    When I click "Source tarball"
    Then I should see the heading "Source tarball"
    And I should see the link "WTFPL"
    And I should see the text "The full source code."

    And I go to the homepage of the "Solution random x name" solution
    # Clean up the asset distribution that was created through the UI.
    Then I delete the "Source tarball" asset distribution

  Scenario: The distribution access URL field should accept multiple file extensions.
    Given I am logged in as a "facilitator" of the "Solution random x name" solution
    When I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    And I click "Add distribution" in the plus button menu
    Then I should see the heading "Add Distribution"

    Given I select the radio button "Upload file"
    Then I should see the description "Allowed types: 7z adf archimate asc aspx bak bat bin bmp bz2 cab cer cml conf css csv dbf deb dgn diff dmg doc docx dwg dxf eap ear ecw emf exe gdms gid gif gml gsb gvl gvp gvspkg gvspki gvt gz hdr hlp jar java jp2 jpeg jpg jpgw js json jsp kml ksh lan log lograster mht msi odg odp ods odt ogv org ott out oxt patch path pdf pem pkg png pod pps ppt pptx prj ps rar raw rdf rmf rst rtf sbn sh shp shx sld sp0 sp1 spx sql swf sym tar tgz tif tiff torrent trig ttf ttl txt type vmdk vmx vrt vsd war wld wsdl xls xlsm xlsx xmi xml xsd xsl xslt zip." for the "Access URL" field

  Scenario: Adding a distribution with a duplicate title
    Given the following solution:
      | title       | Solubility of gases     |
      | description | Affected by temperature |
      | state       | validated               |
    And the following release:
      | title         | 1.0.0 Adolf Sieverts |
      | description   | First public release |
      | is version of | Solubility of gases  |

    # Distributions should have a unique title within a single release.
    And I am logged in as a facilitator of the "Solution random x name" solution
    When I go to the homepage of the "1.0.0 Authoritarian Alpaca" release
    And I click "Add distribution" in the plus button menu
    When I fill in "Title" with "MacOSX binary"
    And I select "WTFPL" from "Licence"
    And I press "Save"
    Then I should have 1 distribution
    When I click "Add distribution" in the plus button menu
    When I fill in "Title" with "MacOSX binary"
    And I select "WTFPL" from "Licence"
    And I press "Save"
    Then I should see the error message "A distribution with title MacOSX binary already exists in this release. Please choose a different title."
    And I should have 1 distribution

    Given I am logged in as a facilitator of the "Solubility of gases" solution
    When I go to the homepage of the "1.0.0 Adolf Sieverts" release
    And I click "Add distribution" in the plus button menu
    When I fill in "Title" with "MacOSX binary"
    And I select "WTFPL" from "Licence"
    And I press "Save"
    Then I should have 2 distributions

    # Clean up the entities created through the user interface.
    Then I delete the "MacOSX binary" asset distribution
    And I delete the "MacOSX binary" asset distribution

  Scenario: Distributions with the same name should not be allowed within the same solution.
    Given the following distribution:
      | title       | Windows - source       |
      | description | Sample description     |
      | access url  | test.zip               |
      | parent      | Solution random x name |
    And I am logged in as a facilitator of the "Solution random x name" solution
    When I go to the homepage of the "Solution random x name" solution
    And I click "Add distribution" in the plus button menu
    When I fill in "Title" with "Windows - source"
    And I select "WTFPL" from "Licence"
    And I press "Save"
    Then I should see the error message "A distribution with title Windows - source already exists in this solution. Please choose a different title."

  Scenario: Licences are not shown in the solution header.
    Given the following licence:
      | title       | Boost Software License                                                         |
      | description | It is a permissive license in the style of the BSD license and the MIT license |
    And distributions:
      | title        | licence                | parent                 |
      | Hot Snake    | WTFPL                  | Solution random x name |
      | Quality Yard | Boost Software License | Solution random x name |

    When I go to the homepage of the "Solution random x name" solution
    # Distributions are still shown in the solution page and so the License text will be visible in the page.
    Then I should not see the text "WTFPL" in the "Header" region
    And I should not see the text "Boost Software License" in the "Header" region
