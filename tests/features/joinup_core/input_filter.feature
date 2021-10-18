@api @group-e
Feature: Input filter
  In order to maintain security
  As a user
  The HTML I can use in the WYSIWYG editor gets filtered

  Background:
    Given the following collection:
      | title | Netflix group |
      | logo  | logo.png      |
      | state | validated     |
    And news content:
      | title                   | headline                           | body                                                                                                                                                                                                                                                                                                                                                                                                                                           | collection    | state     |
      | Jessica Jones returns   | Netflix releases new Marvel series | <iframe width="560" height="315" src="https://www.youtube.com/embed/nWHUjuJ8zxE" frameborder="0" allowfullscreen></iframe>                                                                                                                                                                                                                                                                                                                     | Netflix group | validated |
      | Luke cage               | Some shady iframe                  | <iframe width="50" height="50" src="https://www.example.com" ></iframe>                                                                                                                                                                                                                                                                                                                                                                        | Netflix group | validated |
      | Ragged Crying           | Ragged Crying                      | <h1>test h1</h1> <h2>test h2</h2> <h3>test h3</h3> <h4>test h4</h4> <h5>test h5</h5> <h6>test h6</h6>                                                                                                                                                                                                                                                                                                                                          | Netflix group | validated |
      | Prezi presentation      | Sample prezi.com presentation      | <iframe id="iframe_container" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" src="https://prezi.com/embed/lspajpgcpx1k/?bgcolor=ffffff&amp;lock_to_path=0&amp;autoplay=0&amp;autohide_ctrls=0&amp;landing_data=bHVZZmNaNDBIWnNjdEVENDRhZDFNZGNIUE1va203RnZrY2E1eUhRWTU2WmdSeWd0UjZBc2FKS2wzdUdBTjNtQTJ6Yz0&amp;landing_sign=klSh50F6r1N14DldbUK4G1dqet-bmZ4UbxpQEPOEHzQ" height="400" frameborder="0" width="550"></iframe> | Netflix group | validated |
      | Slideshare presentation | Sample slideshare.net presentation | <iframe src="//www.slideshare.net/slideshow/embed_code/key/hJ3x3pTrtGaatQ" width="595" height="485" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" style="border:1px solid #CCC; border-width:1px; margin-bottom:5px; max-width: 100%;" allowfullscreen> </iframe>                                                                                                                                                            | Netflix group | validated |
      | Google docs             | Sample docs.google.com iframe      | <iframe frameborder="0" height="800" marginheight="0" marginwidth="0" src="https://docs.google.com/forms/d/1dBGzMp9whY2Ibxf4pUQNadpE2C3ywxdDefSSM3BdwJ4/viewform?embedded=true" width="100%">Loading...</iframe>                                                                                                                                                                                                                               | Netflix group | validated |
      | Joinup iframe           | Sample joinup.ec.europa.eu iframe  | <iframe frameborder="0" height="800" marginheight="0" marginwidth="0" src="/homepage" width="100%"></iframe>                                                                                                                                                                                                                                                                                                                                   | Netflix group | validated |
      | Quoted texts            | Quoted texts                       | <q>This is a famous quote.</q> ~ Joinup developer.                                                                                                                                                                                                                                                                                                                                                                                             | Netflix group | validated |
      # Since area coordinates contains commas and we don't want this to be interpreted as multiple values we need to enclose the value with double quotes and escape the quotes in the HTML.
      | Image map               | A map encompasses 1 or more areas  | "<map name=""imagemap""><area shape=""circle"" coords=""50,50,25"" href=""/"" alt=""Homepage""/></map><img usemap=""imagemap"" src=""/images/my-image.png"" alt=""Imagemap example"" />"                                                                                                                                                                                                                                                       | Netflix group | validated |
    And discussion content:
      | title      | body             | collection    | state     |
      | Discussion | Start discussion | Netflix group | validated |

  Scenario: Ensure all required formats are supported in the content editor.
    # 'src' attributes are the url encoded version of the URLs that were inputted prepended by the ec cck url.
    When I go to the "Jessica Jones returns" news
    Then I see the "iframe" element with the "src" attribute set to "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fwww.youtube-nocookie.com%2Fembed%2FnWHUjuJ8zxE%3Fautoplay%3D0%26start%3D0%26rel%3D0" in the "Content" region
    When I go to the "Prezi presentation" news
    Then I see the "iframe" element with the "src" attribute set to "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fprezi.com%2Fembed%2Flspajpgcpx1k" in the "Content" region
    When I go to the "Slideshare presentation" news
    Then I see the "iframe" element with the "src" attribute set to "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fwww.slideshare.net%2Fslideshow%2Fembed_code%2Fkey%2FhJ3x3pTrtGaatQ" in the "Content" region
    When I go to the "Google docs" news
    Then I see the "iframe" element with the "src" attribute set to "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fdocs.google.com%2Fforms%2Fd%2F1dBGzMp9whY2Ibxf4pUQNadpE2C3ywxdDefSSM3BdwJ4%2Fviewform%3Fembedded%3Dtrue" in the "Content" region
    When I go to the "Joinup iframe" news
    # Local urls are not prone to the external cookie consent check.
    Then I see the "iframe" element with the "src" attribute set to "/homepage" in the "Content" region
    When I go to the "Luke cage" news
    Then I should not see the "iframe" element with the "src" attribute set to "https://www.example.com" in the "Content" region
    When I go to the "Quoted texts" news
    Then the response should contain "<q>This is a famous quote.</q> ~ Joinup developer."
    When I go to the "Image map" news
    Then I see the "map" element with the "name" attribute set to "imagemap" in the "Content" region
    And I see the "img" element with the "usemap" attribute set to "imagemap" in the "Content" region
    And the response should contain "<area shape=\"circle\" coords=\"50,50,25\" href=\"/\" alt=\"Homepage\" />"

    Given I am logged in as an authenticated
    When I go to the "Discussion" discussion
    And I fill in "Create comment" with "<q>Quoted</q>"
    And I wait for the spam protection time limit to pass
    When I press "Post comment"
    Then the page should contain the html text "<q>Quoted</q>"

  @javascript
  Scenario: Tags h1, h5, h6 can exist in a formatted text but the user does not have these options on the editor.
    When I am logged in as a moderator
    And I go to the "Ragged Crying" news
    Then I should see an "h1" element with the text "test h1" in the "Content" region
    Then I should see an "h2" element with the text "test h2" in the "Content" region
    Then I should see an "h3" element with the text "test h3" in the "Content" region
    Then I should see an "h4" element with the text "test h4" in the "Content" region
    Then I should see an "h5" element with the text "test h5" in the "Content" region
    Then I should see an "h6" element with the text "test h6" in the "Content" region

    # Ensure that the user does not have access to disallowed paragraph formats.
    And I open the header local tasks menu
    And I click "Edit" in the "Entity actions" region
    Then the paragraph formats in the "Content" field should not contain the "h1, h5, h6" formats
