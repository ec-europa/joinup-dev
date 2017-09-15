@api
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
      | title                   | headline                           | body                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | collection    | state     |
      | Jessica Jones returns   | Netflix releases new Marvel series | value: <iframe width="560" height="315" src="https://www.youtube.com/embed/nWHUjuJ8zxE" frameborder="0" allowfullscreen></iframe> - format: content_editor                                                                                                                                                                                                                                                                                                                     | Netflix group | validated |
      | Luke cage               | Some shady iframe                  | value: <iframe width="50" height="50" src="https://www.example.com" ></iframe> - format: content_editor                                                                                                                                                                                                                                                                                                                                                                        | Netflix group | validated |
      | Ragged Crying           | Ragged Crying                      | value: <h1>test h1</h1> <h2>test h2</h2> <h3>test h3</h3> <h4>test h4</h4> <h5>test h5</h5> <h6>test h6</h6> - format: content_editor                                                                                                                                                                                                                                                                                                                                          | Netflix group | validated |
      | Prezi presentation      | Sample prezi.com presentation      | value: <iframe id="iframe_container" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" src="https://prezi.com/embed/lspajpgcpx1k/?bgcolor=ffffff&amp;lock_to_path=0&amp;autoplay=0&amp;autohide_ctrls=0&amp;landing_data=bHVZZmNaNDBIWnNjdEVENDRhZDFNZGNIUE1va203RnZrY2E1eUhRWTU2WmdSeWd0UjZBc2FKS2wzdUdBTjNtQTJ6Yz0&amp;landing_sign=klSh50F6r1N14DldbUK4G1dqet-bmZ4UbxpQEPOEHzQ" height="400" frameborder="0" width="550"></iframe> - format: content_editor | Netflix group | validated |
      | Slideshare presentation | Sample slideshare.net presentation | value: <iframe src="//www.slideshare.net/slideshow/embed_code/key/hJ3x3pTrtGaatQ" width="595" height="485" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" style="border:1px solid #CCC; border-width:1px; margin-bottom:5px; max-width: 100%;" allowfullscreen> </iframe> - format: content_editor                                                                                                                                                            | Netflix group | validated |
      | Google docs             | Sample docs.google.com iframe      | value: <iframe frameborder="0" height="800" marginheight="0" marginwidth="0" src="https://docs.google.com/forms/d/1dBGzMp9whY2Ibxf4pUQNadpE2C3ywxdDefSSM3BdwJ4/viewform?embedded=true" width="100%">Loading...</iframe> - format: content_editor                                                                                                                                                                                                                               | Netflix group | validated |
      | Joinup iframe           | Sample joinup.ec.europa.eu iframe  | value: <iframe frameborder="0" height="800" marginheight="0" marginwidth="0" src="/homepage" width="100%"></iframe> - format: content_editor                                                                                                                                                                                                                                                                                                                                   | Netflix group | validated |

  Scenario: Ensure all required formats are supported in the content editor.
    When I go to the "Jessica Jones returns" news
    Then I should see the "iframe" element in the Content region
    Then I see the "iframe" element with the "src" attribute set to "https://www.youtube.com/embed/nWHUjuJ8zxE" in the "Content" region
    When I go to the "Prezi presentation" news
    Then I see the "iframe" element with the "src" attribute set to "https://prezi.com/embed/lspajpgcpx1k" in the "Content" region
    When I go to the "Slideshare presentation" news
    Then I see the "iframe" element with the "src" attribute set to "//www.slideshare.net/slideshow/embed_code/key/hJ3x3pTrtGaatQ" in the "Content" region
    When I go to the "Google docs" news
    Then I see the "iframe" element with the "src" attribute set to "https://docs.google.com/forms/d/1dBGzMp9whY2Ibxf4pUQNadpE2C3ywxdDefSSM3BdwJ4/viewform?embedded=true" in the "Content" region
    When I go to the "Joinup iframe" news
    Then I see the "iframe" element with the "src" attribute set to "/homepage" in the "Content" region
    When I go to the "Luke cage" news
    Then I should not see the "iframe" element with the "src" attribute set to "https://www.example.com" in the "Content" region

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

  Scenario: As a community content editor I can embed accepted video iframes
  into the content field. European Commission videos short URLs are resolved
  and videos from providers that are not in the 'allowed providers' are
  stripped out.
    Given the following collections:
      | title      | description                                 | logo     | banner     | state     |
      | Metal fans | "Share the love for nickel, tungsten & co." | logo.png | banner.jpg | validated |

    Given I am logged in as a "facilitator" of the "Metal fans" collection
    And I go to the homepage of the "Metal fans" collection
    And I click "Add news" in the plus button menu

    Then I fill in the following:
      | Headline | United Kingdom Brexit Notification |
      | Kicker   | Brexit                             |
    And I fill in "Content" with:
      """
      <h2>All bellow videos have 'autoplay' set to TRUE</h2>
      European Commission videos are allowed.
      <iframe src="https://ec.europa.eu/avservices/play.cfm?ref=I072651&videolang=EN&starttime=0&autostart=true" id="videoplayer" width="852" height="480" frameborder="0" scrolling="no" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>
      European Commission videos (with short URL that will be resolved) are allowed.
      <iframe src="http://europa.eu/!dV74uw" width="852" height="480" frameborder="0" scrolling="no" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>
      YouTube videos are allowed.
      <iframe width="560" height="315" src="https://www.youtube.com/embed/xlnYVHRp128?autoplay=1" frameborder="0" allowfullscreen></iframe>
      Vimeo videos are NOT allowed (yet).
      <iframe src="https://player.vimeo.com/video/225133231?autoplay=1" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
      DailyMotion videos are NOT allowed (yet).
      <iframe frameborder="0" width="480" height="270" src="//www.dailymotion.com/embed/video/x5vl5l0?autoPlay=1" allowfullscreen></iframe>
      """

    Given I press "Publish"
    # All allowed videos have now the autoplay set to FALSE.
    Then the response should contain "//ec.europa.eu/avservices/play.cfm?ref=I072651&amp;lg=EN&amp;starttime=0&amp;autoplay=false"
    And the response should contain "//ec.europa.eu/avservices/play.cfm?ref=I136289&amp;lg=en&amp;starttime=0&amp;autoplay=false"
    And the response should contain "https://www.youtube.com/embed/xlnYVHRp128?autoplay=0&amp;start=0&amp;rel=0"

    And the response should contain "https://player.vimeo.com/video/225133231"
    And the response should not contain "//www.dailymotion.com/embed/video/x5vl5l0"
