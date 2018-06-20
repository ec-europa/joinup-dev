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
