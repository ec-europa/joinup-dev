@api
Feature: Embed of videos into the page.
  In order to show videos regarding my content
  As a user of the website
  I should be able to embed a restricted set of videos in the page.

  Background:
    Given the following collection:
      | title       | Beer brewing corporation             |
      | description | Beer is the real nectar of the gods. |
      | state       | validated                            |

  Scenario: As a community content editor I can embed video iframes from allowed providers into the content field.
    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
    And I click "Add news" in the plus button menu

    Then I fill in the following:
      | Headline | United Kingdom Brexit Notification |
      | Kicker   | Brexit                             |
    And I fill in "Content" with:
      """
      <h2>All below videos have 'autoplay' set to TRUE</h2>
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

    But the response should not contain "https://player.vimeo.com/video/225133231"
    And the response should not contain "//www.dailymotion.com/embed/video/x5vl5l0"

  @javascript
  Scenario Outline: A video embed button should be shown to community content editors.
    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
    When I open the plus button menu
    And I click "Add news"
    When I fill in the following:
      | Headline | Some test video |
      | Kicker   | Some test video |
    And I press the button "Video Embed" in the "Content" wysiwyg editor
    Then a modal should open
    And I should see the text "Youtube and EC videos are allowed."
    And I should see the text "Youtube example: https://www.youtube.com/watch?v=123456789abcd"
    And I should see the text "EC url example: http://europa.eu/123abc!123"
    And I should see the text "EC video example: https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=ABC12345"
    When I fill in "Video URL" with "<url>"
    And I press "Save" in the "Modal buttons" region
    Then the modal should be closed
    And I press "Save as draft"
    Then the response should contain "<embed url>"

    Examples:
      | url                                                                      | embed url                                                                                   |
      | https://www.youtube.com/watch?v=YTaLmMsaLOg                              | https://www.youtube.com/embed/YTaLmMsaLOg?autoplay=0&amp;start=0&amp;rel=0                  |
      | http://europa.eu/!dV74uw                                                 | //ec.europa.eu/avservices/play.cfm?ref=I136289&amp;lg=en&amp;starttime=0&amp;autoplay=false |
      | https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I156836 | //ec.europa.eu/avservices/play.cfm?ref=I156836&amp;lg=en&amp;starttime=0&amp;autoplay=false |

  Scenario: Forcing auto-play into the content of an entity will not trigger the auto-play.
    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
    And I click "Add news"
    When I fill in the following:
      | Headline | Some test video |
      | Kicker   | Some test video |
    And I fill in "Content" with:
    """
    <p>{"preview_thumbnail":"/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/r5Kd7ltWS9w.jpg?itok=2PfetCfJ","video_url":"https://www.youtube.com/watch?v=r5Kd7ltWS9w","settings":{"responsive":true,"width":"854","height":"480","autoplay":true},"settings_summary":["Embedded Video (Responsive)."]}</p>
    """
    And I press "Publish"
    Then the response should contain "autoplay=0"
