@api @group-a
Feature: About page
  In order to present an overview of the purpose of my collection
  As a collection owner
  I want to publish details about the collection on the About page

  @terms
  Scenario: View collection detailed information in the About page
    Given the following owner:
      | name         | type                |
      | Tamsin Irwin | Industry consortium |
    And the following contact:
      | email       | irwinbvba@example.com        |
      | name        | Irwin BVBA made-up company   |
      | Website URL | http://www.example.org/irwin |
    And the following collection:
      | title               | Fitness at work                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
      | description         | <p>This collection is intended to show ways of being <strong>fit while working</strong>.</p><p>Integer diam purus molestie in est sit amet tincidunt gravida dolor. Vivamus dui nisi semper et tellus a lobortis tristique felis. Praesent sagittis orci id sodales finibus. Morbi purus urna imperdiet vitae est a porta semper dui. Curabitur scelerisque non mi at facilisis. Nullam blandit euismod ipsum vel varius arcu fermentum nec. In ligula sapien tempor non venenatis ac tincidunt sed nunc. In consequat sapien risus a malesuada eros auctor eget. Curabitur at ultricies mi at varius nunc. Orci varius natoque penatibus et magnis dis parturient montes nascetur ridiculus mus. Curabitur egestas massa nec semper sagittis orci urna semper nulla at dictum ligula ipsum sit amet urna. Fusce euismod luctus ullamcorper. In quis porttitor arcu.</p> |
      | topic               | E-health                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
      | owner               | Tamsin Irwin                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
      | abstract            | <strong>Fit while working</strong> is dope. Lorem ipsum dolor sit amet consectetur adipiscing elit. Suspendisse diam nunc blandit vitae faucibus nec laoreet sit amet lectus. Cras faucibus augue velit et aliquet sem dictum vel. Aenean rutrum iaculis imperdiet. Proin faucibus varius turpis a fringilla ante sodales non. Donec vel purus metus. Fusce pellentesque eros dolor. Donec tempor ipsum id erat ullamcorper pulvinar. Pellentesque eget dolor nunc. Vivamus libero leo blandit a ornare non sollicitudin iaculis purus. Integer nec enim facilisis mi fermentum mollis sed vitae lacus.                                                                                                                                                                                                                                                                  |
      | contact information | Irwin BVBA made-up company                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
      | spatial coverage    | Belgium                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | closed              | no                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
      | content creation    | members                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
      | moderation          | no                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
      | state               | validated                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |

    When I go to the homepage of the "Fitness at work" collection
    # Check for HTML so that we can assert that text styling is present.
    Then the page should contain the html text "<strong>Fit while working</strong> is dope"
    And I should see the text "leo blandit a ornare non sollicitudin iaculis…"
    # Check that later chunks of text in the abstract are not rendered.
    But I should not see the text "purus. Integer nec enim facilisis mi fermentum mollis sed vitae lacus" in the Content region
    And I should not see the text "This collection is intended to show ways of being fit while working"

    # The 'Read more' link leads to the About page.
    When I click "Read more" in the "Content" region
    Then I should see the heading "About Fitness at work"

    And I should see the text "Fit while working is dope"
    And I should see the text "This collection is intended to show ways of being fit while working"
    And I should see the text "Tamsin Irwin"
    And I should see the text "Irwin BVBA made-up company"
    # The following 2 fields should not be visible after change request in ISAICP-3664.
    And I should not see the text "E-health"
    And I should not see the text "Belgium"

    # There should be a section explaining how the collection is moderated.
    And I should see the heading "Moderation"
    And I should see the text "Open collection"
    And I should see the text "Only members can create content."
    And I should see the text "Non moderated"
    # Regression test for the "Moderation" label appearing twice.
    And the text "Moderation" should appear 1 time

    # When there is no abstract, the description should be shown in the homepage.
    When I am logged in as a "moderator"
    And I go to the edit form of the "Fitness at work" collection
    And I fill in "Abstract" with ""
    And I press "Publish"
    Then I should see the heading "Fitness at work"
    And the page should contain the html text "This collection is intended to show ways of being <strong>fit while working</strong>"
    And I should see the text "In consequat sapien risus a malesuada…" in the Content region
    But I should not see the text "Vivamus libero leo blandit a ornare non sollicitudin iaculis" in the Content region
    And I should not see the text "malesuada eros auctor eget. Curabitur at" in the Content region
    When I click "Read more" in the "Content" region
    Then I should see the heading "About Fitness at work"
