@api @terms @group-g
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
      | Headline    | United Kingdom Brexit Notification |
      | Short title | Brexit                             |
    And I select "Supplier exchange" from "Topic"
    And I fill in "Content" with:
      """
      <h2>All below videos have 'autoplay' set to TRUE</h2>
      European Commission videos are allowed.
      <iframe src="https://ec.europa.eu/avservices/play.cfm?ref=I072651&lg=EN&starttime=0&autostart=true" id="videoplayer" width="852" height="480" frameborder="0" scrolling="no" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>
      European Commission videos are allowed.
      <iframe src="https://ec.europa.eu/avservices/play.cfm?ref=I-087075&lg=EN&starttime=0&autostart=true" id="videoplayer" width="852" height="480" frameborder="0" scrolling="no" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>
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
    Then the response should contain "//europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI072651%26lg%3DEN%26starttime%3D0%26autoplay%3Dfalse"
    And the response should contain "//europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI-087075%26lg%3Den%26starttime%3D0%26autoplay%3Dfalse"
    And the response should contain "//europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI-136289%26lg%3Den%26starttime%3D0%26autoplay%3Dfalse"
    And the response should contain "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fwww.youtube-nocookie.com%2Fembed%2FxlnYVHRp128%3Fautoplay%3D0%26start%3D0%26rel%3D0"
    And the response should contain "//europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fplayer.vimeo.com%2Fvideo%2F225133231"
    But the response should not contain "//europa.eu/webtools/crs/iframe/?oriurl=%2F%2Fwww.dailymotion.com%2Fembed%2Fvideo%2Fx5vl5l0"

  @javascript
  Scenario Outline: A video embed button should be shown to community content editors.
    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
    When I open the plus button menu
    And I click "Add news"
    When I fill in the following:
      | Headline    | Some test video |
      | Short title | Some test video |
    And I select "Supplier exchange" from "Topic"
    And I press the button "Video Embed" in the "Content" wysiwyg editor
    Then a modal should open
    And I should see the text "Youtube and EC videos are allowed."
    And I should see the text "Youtube example: https://www.youtube.com/watch?v=dQw4w9WgXcQ"
    And I should see the text "EC url example: http://europa.eu/123abc!123"
    And I should see the text "EC video example: https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=ABC12345"
    When I fill in "Video URL" with "<url>"
    And I press "Save" in the "Modal buttons" region
    Then the modal should be closed
    And I press "Save as draft"
    Then the response should contain "<embed url>"

    Examples:
      | url                                                                        | embed url                                                                                                                                                 |
      | https://www.youtube.com/watch?v=YTaLmMsaLOg                                | //europa.eu/webtools/crs/iframe/?oriurl=https%3A%2F%2Fwww.youtube-nocookie.com%2Fembed%2FYTaLmMsaLOg%3Fautoplay%3D0%26start%3D0%26rel%3D0                 |
      | http://europa.eu/!dV74uw                                                   | //europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI-136289%26lg%3Den%26starttime%3D0%26autoplay%3Dfalse |
      | https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I156836   | //europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI156836%26lg%3Den%26starttime%3D0%26autoplay%3Dfalse  |
      | https://audiovisual.ec.europa.eu/embed/index.html?sitelang=en&ref=I-087075 | //europa.eu/webtools/crs/iframe/?oriurl=%2F%2Faudiovisual.ec.europa.eu%2Fembed%2Findex.html%3Fref%3DI-087075%26lg%3Den%26starttime%3D0%26autoplay%3Dfalse |

  Scenario: Forcing auto-play into the content of an entity will not trigger the auto-play.
    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
    And I click "Add news"
    When I fill in the following:
      | Headline    | Some test video |
      | Short title | Some test video |
    And I select "Supplier exchange" from "Topic"
    And I fill in "Content" with:
    """
    <p>{"preview_thumbnail":"/sites/default/files/styles/video_embed_wysiwyg_preview/public/video_thumbnails/r5Kd7ltWS9w.jpg?itok=2PfetCfJ","video_url":"https://www.youtube.com/watch?v=r5Kd7ltWS9w","settings":{"responsive":true,"width":"854","height":"480","autoplay":true},"settings_summary":["Embedded Video (Responsive)."]}</p>
    """
    And I press "Publish"
    # The response contains an encoded version of the 'autoplay=0' since it is passed to the ec cck domain.
    Then the response should contain "autoplay%3D0"

    When I am not logged in
    And I go to the "Some test video" news
    Then the response should contain "autoplay%3D0"
