@api
Feature: Embed of videos into the page.
  In order to show videos regarding my content
  As a user of the website
  I should be able to embed a restricted set of videos in the page.

  Scenario: As a community content editor I can embed video iframes from allowed providers into the content field.
    Given the following collection:
      | title       | Beer brewing corporation             |
      | description | Beer is the real nectar of the gods. |
      | state       | validated                            |

    Given I am logged in as a "facilitator" of the "Beer brewing corporation" collection
    And I go to the homepage of the "Beer brewing corporation" collection
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

    But the response should not contain "https://player.vimeo.com/video/225133231"
    And the response should not contain "//www.dailymotion.com/embed/video/x5vl5l0"