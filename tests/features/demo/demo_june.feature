@api
Feature: June 2016 demo
  As users of the website
  I should be able to interact with the website and manage content.

  Scenario: Manage collection and view collection scenarios.
    Given solutions:
      | title                     | description                                | documentation | moderation | state     |
      | Avengers initiative       | Gather the strongest into a group.         | text.pdf      | no         | validated |
      | Project Tahiti            | Rejuvenate deadly wounds and erase memory. | text.pdf      | yes        | validated |
      | Hellicarrier              | Provide a flying fortress as headquarters. | text.pdf      | no         | validated |
      | Project 'Captain America' | Bring 'Captain america' back into action.  | text.pdf      | yes        | validated |
    And collections:
      | title          | description                          | logo     | moderation | affiliates                                                                   | state     |
      | S.H.I.E.L.D.   | Well, they are mostly flying around. | logo.png | yes        | Avengers initiative, Project Tahiti, Hellicarrier, Project 'Captain America' | validated |
      | x-Men          | Based on Professor Xavier's mansion. | logo.png | no         |                                                                              | validated |
      | Avengers       | Based on Tony stark's tower.         | logo.png | yes        |                                                                              | validated |
      | Fantastic four | Based on Reed Richard's tower.       | logo.png | yes        |                                                                              | validated |

    And users:
      | name      | pass                 | mail                    | roles     |
      | Stan lee  | cameoineverymovie    | stan.lee@example.com    | moderator |
      | Nick Fury | ihaveasecret         | nick.fury@example.com   |           |
      | Wolverine | smellslikemetalclaws | logan.x.men@example.com |           |
    And collection user memberships:
      | collection   | user      | roles                      |
      | S.H.I.E.L.D. | Nick Fury | owner, facilitator, member |
      | x-Men        | Wolverine | facilitator, member        |
      | Avengers     | Wolverine | member                     |

    And news content:
      | title                       | headline                                  | body                                                                                                                                | status    |
      | Phil Coulson is down        | Phil Coulson fell by the hands of Loki.   | Phil Coulson tried to stop Loki from escaping and was killed by him.                                                                | published |
      | Phoenix is down             | Wolverine took down Jean Gray.            | In an epic battle, Wolverine had to give the final blow to his great love, Jean Gray as she lost control to the Phoenix inside her. | published |
      | S.H.I.E.L.D. is infiltrated | Winter soldier was spotted in action.     | As S.H.I.E.L.D. Hellicarrier is being taken down by the Winter soldier, we are also trying to spot the Hydra agents.                | published |
      | Captain America not dead?   | Captain America found in the ice.         | Captain America's body was found intact and preserved in ice.                                                                       | published |
      | Hellicarrier under attack   | The Hellicarrier was attacked by Loki.    | Loki and his servants have attacked us. Hawkeye took out one engine.                                                                | published |
      | Captain America & Avengers  | Captain America to lead the avengers?     | It is S.H.I.E.L.D.'s opinion that someone like Captain America can be a good leader for avengers.                                   | published |
      | Project Tahiti case 1       | Top secret: We are bringing Coulson back. | His memories must be wiped out throughout the process.                                                                              | published |
      | Who is Winter soldier?      | Captain America's child friend is alive?  | As it turns out the Hydra's agent-Winter soldier-is no other than Bucky-Captain's America childhood friend.                         | published |
    And the following "news" content belong to the corresponding collections:
      | content                     | collection   |
      | Phil Coulson is down        | S.H.I.E.L.D. |
      | Phoenix is down             | x-Men        |
      | S.H.I.E.L.D. is infiltrated | S.H.I.E.L.D. |
    And the following "news" content belong to the corresponding solutions:
      | content                    | solution                  |
      | Captain America not dead?  | Project 'Captain America' |
      | Hellicarrier under attack  | Hellicarrier              |
      | Captain America & Avengers | Avengers initiative       |
      | Project Tahiti case 1      | Project Tahiti            |
      | Who is Winter soldier?     | Project 'Captain America' |

    And custom_page content:
      | title              | body                                                                                                                              |
      | S.H.I.E.L.D. Home  | Welcome to S.H.I.E.L.D. webspace. <br />You can find anything about S.H.I.E.L.D. here.                                            |
      | About S.H.I.E.L.D. | S.H.I.E.L.D. stands for Strategic Homeland Intervention Enforcement and Logistics Division. That's all you need to know about it. |
      | List of members    | Here is a list of members known to the public: <br><ul><li>Nick Fury</li></ul>                                                    |
    And the following "custom_page" content belong to the corresponding collections:
      | content            | collection   |
      | S.H.I.E.L.D. Home  | S.H.I.E.L.D. |
      | About S.H.I.E.L.D. | S.H.I.E.L.D. |
      | List of members    | S.H.I.E.L.D. |
    And the following "custom_page" content menu items for the corresponding collections:
      | collection   | label              | page               | weight |
      | S.H.I.E.L.D. | S.H.I.E.L.D. Home  | S.H.I.E.L.D. Home  | 1      |
      | S.H.I.E.L.D. | About S.H.I.E.L.D. | About S.H.I.E.L.D. | 2      |
      | S.H.I.E.L.D. | List of members    | List of members    | 3      |

    # Scenario A. A collection owner manages his own collection.
    And I am on the homepage
    When I go to "/user"
    And I fill in "Username" with "Nick Fury"
    And I fill in "Password" with "ihaveasecret"
    And I press "Log in"
    # Login was successful if I see my profile page.
    Then I should see the heading "Nick Fury"
    And I should see the link "Collections"

    # Collections overview.
    When I click "Collections"
    Then I should see the text "S.H.I.E.L.D."
    And I should see the text "x-Men"
    And I should see the text "Avengers"
    And I should see the text "Fantastic four"

    # Collection overview.
    When I click "S.H.I.E.L.D"
    # Solutions belonging to the collection.
    Then I should see the text "Avengers initiative"
    And I should see the text "Project Tahiti"
    And I should see the text "Hellicarrier"
    And I should see the text "Project 'Captain America'"
    # News belonging to the collection.
    And I should see the text "Phil Coulson is down"
    And I should see the text "S.H.I.E.L.D. is infiltrated"
    # News belonging to a solution.
    And I should not see the text "Captain America not dead?"
    And I should not see the text "Hellicarrier under attack"
    And I should not see the text "Captain America & Avengers"
    And I should not see the text "Project Tahiti case 1"
    And I should not see the text "Who is Winter soldier?"
    # News of other collections.
    And I should not see the text "Phoenix is down"

    # See menu items.
    Then I should see the following collection menu items in the specified order:
      | text               |
      | Overview           |
      | About              |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |

    # View the three custom_pages.
    When I click "S.H.I.E.L.D. Home"
    Then I should see the heading "S.H.I.E.L.D. Home"
    And I should see the text "Welcome to S.H.I.E.L.D. webspace. <br />You can find anything about S.H.I.E.L.D. here."
    When I click "About S.H.I.E.L.D."
    Then I should see the heading "About S.H.I.E.L.D."
    And I should see the text "S.H.I.E.L.D. stands for Strategic Homeland Intervention Enforcement and Logistics Division. That's all you need to know about it."
    When I click "List of members"
    Then I should see the heading "List of members"
    And I should see the text "Here is a list of members known to the public:"

    # Add new custom page.
    When I click "Add custom page" in the plus button menu
    And I enter "How to apply" for "Title"
    And I enter "You want to become a S.H.I.E.L.D. agent? <br />If you were worthy, S.H.I.E.L.D. <b>would have found you already</b>. <br />GET OUT." in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the heading "How to apply"
    And I should see the text "You want to become a S.H.I.E.L.D. agent?"
    And  I should see the following collection menu items in the specified order:
      | text               |
      | Overview           |
      | About              |
    # @todo: When ISAICP-2369 is in, this menu item should be moved to the end of the list.
      | How to apply       |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |

    # Link another page to this one. The other page should be on the menu.
    And I should see the text "List of members"
    When I click "List of members"
    Then I should see the heading "List of members"

    # Edit page.
    When I click "Edit"
    Then I should see the heading "Edit Custom page List of members"
    # @todo: We have to set the link to the other page.
    When I enter "Here is a list of members known to the public: <br><ul><li>Nick Fury</li></ul><br />Want to apply? Check the other page for this." in the "Body" wysiwyg editor
    And I press "Save"
    Then I should see the heading "List of members"
    And I should see the text "Want to apply? Check the other page for this."

    # Add news.
    When I click "Add news" in the plus button menu
    Then I should see the heading "Add news"
    And I fill in the following:
      | Kicker   | New York under attack          |
      | Headline | S.H.I.E.L.D. to nuke New York? |
    And I enter "In a desperate attempt to stop the nuke, Nick fury shot down an airplane of S.H.I.E.L.D." in the "Content" wysiwyg editor
    And I press "Save as draft"
    Then I should see the heading "New York under attack"
    And I should see the text "S.H.I.E.L.D. to nuke New York?"
    # Content is saved as draft but should be viewable by the content owner on
    # the collection overview.
    When I go to the homepage of the "S.H.I.E.L.D." collection
    Then I should see the link "New York under attack"

    # Scenario B: A non member registered user, browses the website.
    When I am logged in as "Wolverine"
    Then I should see the link "Collections"

    # Collections overview.
    # @todo Remove this line when caching Search API results is fixed.
    # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2574
    When the cache has been cleared
    When I click "Collections"
    Then I should see the text "S.H.I.E.L.D."
    And I should see the text "x-Men"
    And I should see the text "Avengers"
    And I should see the text "Fantastic four"

    # Collection overview.
    When I click "S.H.I.E.L.D"
    # Solutions belonging to the collection.
    Then I should see the text "Avengers initiative"
    And I should see the text "Project Tahiti"
    And I should see the text "Hellicarrier"
    And I should see the text "Project 'Captain America'"
    # News belonging to the solution.
    And I should see the text "Phil Coulson is down"
    And I should see the text "S.H.I.E.L.D. is infiltrated"
    # The draft news article should not be visible to a non-member.
    And I should not see the text "New York under attack"
    # News from solutions.
    And I should not see the text "Captain America not dead?"
    And I should not see the text "Hellicarrier under attack"
    And I should not see the text "Captain America & Avengers"
    And I should not see the text "Project Tahiti case 1"
    And I should not see the text "Who is Winter soldier?"
    # News of other collections.
    And I should not see the text "Phoenix is down"

    # See menu items.
    Then I should see the following collection menu items in the specified order:
      | text               |
      | Overview           |
      | About              |
    # @todo: When ISAICP-2369 is in, this menu item should be moved to the end of the list.
      | How to apply       |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |
