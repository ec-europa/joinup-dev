Feature: June 2016 demo
  As users of the website
  I should be able to interact with the website and manage content.

  Scenario: Manage collection and view collection scenarios.
    Given collections:
      | title          | description                           | logo     | moderation |
      | S.H.I.E.L.D.   | Well, they are mostly flying around.  | logo.png | yes        |
      | x-Men          | Based on Proffessor Xavier's mantion. | logo.png | no         |
      | Avengers       | Based on Tony stark's tower.          | logo.png | yes        |
      | Fantastic four | Based on Reed Richard's tower.        | logo.png | yes        |
    And solutions:
      | title                     | description                                | documentation | moderation | collection   |
      | Avengers initiative       | Gather the strongest into a group.         | text.pdf      | no         | S.H.I.E.L.D. |
      | Project Hawaii            | Rejuvenate deadly wounds and erase memory. | text.pdf      | yes        | S.H.I.E.L.D. |
      | Hellicarrier              | Provide a flying fortress as headquarters. | text.pdf      | no         | S.H.I.E.L.D. |
      | Project 'Captain America' | Bring 'Captain america' back into action.  | text.pdf      | yes        | S.H.I.E.L.D. |
    And users:
      | name      | pass                 | mail                    | roles     |
      | Stan lee  | cameoineverymovie    | stan.lee@example.com    | moderator |
      | Nick Fury | ihaveasecret         | nick.fury@example.com   |           |
      | Wolverine | smellslikemetalclaws | logan.x.men@example.com |           |
    And user memberships:
      | collection   | user      | roles                 |
      | S.H.I.E.L.D. | Nick Fury | administrator, member |
      | x-Men        | Wolverine | facilitator, member   |
      | Avengers     | Wolverine | member                |
    # The og_group_ref references to a collection and the field_news_parent to a solution.
    And news content:
      | Headline                    | Kicker                                    | Content                                                                                                                             | og_group_ref | field_news_parent         |
      | Captain America not dead?   | Captain America found in the ice.         | Captain America's body was found intact and preserved in ice.                                                                       |              | Project 'Captain America' |
      | Hellicarrier under attack   | The Hellicarrier was attacked by Loki.    | Loki and his servants have attacked us. Hawkeye took out one engine.                                                                |              | Hellicarrier              |
      | Phil Coulson is down        | Phil Coulson fell by the hands of Loki.   | Phil Coulson tried to stop Loki from escaping and was killed by him.                                                                | S.H.I.E.L.D  |                           |
      | Captain America & Avengers  | Captain America to lead the avengers?     | It is S.H.I.E.L.D.'s opinion that someone like Captain America can be a good leader for avengers.                                   |              | Avengers initiative       |
      | Project Hawaii case 1       | Top secret: We are bringing Coulson back. | His memories must be wiped out throughout the process                                                                               |              | Project Hawaii            |
      | Phoenix is down             | Wolverine took down Jean Gray.            | In an epic battle, Wolverine had to give the final blow to his great love, Jean Gray as she lost control to the Phoenix inside her. | x-Men        |                           |
      | S.H.I.E.L.D. is infiltrated | Winter soldier was spotted in action.     | As S.H.I.E.L.D. Hellicarrier is being taken down by the Winter soldier, we are also trying to spot the Hydra agents.                | S.H.I.E.L.D. |                           |
      | Who is Winter soldier?      | Captain America's child friend is alive?  | As it turns out, Hydra's agent, Winter soldier is no other than Bucky, Captain's America childhood friend.                          |              | Project 'Captain America' |
    And custom_page content:
      | title              | body                                                                                                                               | group audience |
      | S.H.I.E.L.D. Home  | Welcome to S.H.I.E.L.D. webspace. <br />You can find anything about S.H.I.E.L.D. here.                                             | S.H.I.E.L.D.   |
      | About S.H.I.E.L.D. | S.H.I.E.L.D. stands for Strategic Homeland Intervention, Enforcement and Logistics Division. That's all you need to know about it. | S.H.I.E.L.D.   |
      | List of members    | Here is a list of members known to the public: <br><ul><li>Nick Fury</li></ul>                                                     | S.H.I.E.L.D.   |

    # Scenario A. A collection owner manages his own collection.
    And I am on the homepage
    When I go to "/user"
    And I fill in "Username" with "Nick Fury"
    And I fill in "Password" with "ihaveasecret"
    And I press "Login"
    # Login was successful if I see my profile page.
    Then I should see the heading "Nick Fury"
    And I should see the link "Collections"

    # Collections overview.
    When I click "Collections"
    Then I should see the heading "S.H.I.E.L.D."
    And I should see the heading "x-Men"
    And I should see the heading "Avengers"
    And I should see the heading "Fantastic four"

    # Collection overview.
    When I click "S.H.I.E.L.D"
    Then I should see the heading "Avengers initiative"
    And I should see the heading "Project Hawaii"
    And I should see the heading "Hellicarrier"
    And I should see the heading "Project 'Captain America'"
    And I should see the heading "Captain America not dead?"
    And I should see the heading "Hellicarrier under attack"
    And I should see the heading "Phil Coulson is down"
    And I should see the heading "Captain America & Avengers"
    And I should see the heading "Project Hawaii case 1"
    And I should see the heading "Phoenix is down"
    And I should see the heading "S.H.I.E.L.D. is infiltrated"
    And I should see the heading "Who is Winter soldier?"

    # See menu items.
    Then I should see the following collection menu items in the specified order:
      | text               |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |

    # View the three custom_pages.
    When I click "S.H.I.E.L.D. Home"
    Then I should see the heading "S.H.I.E.L.D. Home"
    And I should see the text "Welcome to S.H.I.E.L.D. webspace. <br />You can find anything about S.H.I.E.L.D. here."
    When I click "About S.H.I.E.L.D."
    Then I should see the heading "About S.H.I.E.L.D."
    And I should see the text "S.H.I.E.L.D. stands for Strategic Homeland Intervention, Enforcement and Logistics Division. That's all you need to know about it."
    When I click "List of members"
    Then I should see the heading "List of members"
    And I should see the text "Here is a list of members known to the public:"
    # Also do a sample check for the visibility of the collection actions.
    And I should see the link "Add custom page"

    # Add new custom page.
    When I click "Add custom page"
    And I fill in the following:
      | title | How to apply                                                                                                                        |
      | Body  | You want to become a S.H.I.E.L.D. agent? <br />If you were worthy, S.H.I.E.L.D. <b>would have found you already</b>. <br />GET OUT. |
    And I press "Save and publish"
    Then I should see the heading "How to apply"
    And I should see the text "You want to become a S.H.I.E.L.D. agent?"
    And  I should see the following collection menu items in the specified order:
      | text               |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |

    # Link another page to this one. The other page should be on the menu.
    And I should see the text "List of members"
    When I click "List of members"
    Then I should see the heading "List of members"
    # This step is unnecessary.
    And I should see the text "Edit"

    # Edit page.
    When I click "Edit"
    Then I should see the heading "Edit List of members"
  @todo: We have to set the link to the other page.
    When I fill in "Body" with "Here is a list of members known to the public: <br><ul><li>Nick Fury</li></ul><br />Want to apply? Check the other page for this."
    And I press "Save"
    Then I should see the heading "List of members"
    And I should see the text "Want to apply? Check the other page for this."
    # Also check for the visibility of the collection action 'Add news'.
    And I should see the link "Add news"

    # Add news.
    When I click "Add news"
    Then I should see the heading "Add news"
    And I fill in the following:
      | Headline | New York under attack                                                                    |
      | Kicker   | S.H.I.E.L.D. to nuke New York?                                                           |
      | Content  | In a desperate attempt to stop the nuke, Nick fury shot down an airplane of S.H.I.E.L.D. |
    And I press "Save"
    And I should see the heading "New York under attack"
    And I should see the text "S.H.I.E.L.D. to nuke New York?"

    # Scenario B: A non member registered user, browses the website.
    When I am logged in as "Wolverine"
    Then I should see the link "Collections"

    # Collections overview.
    When I click "Collections"
    Then I should see the heading "S.H.I.E.L.D."
    And I should see the heading "x-Men"
    And I should see the heading "Avengers"
    And I should see the heading "Fantastic four"

    # Collection overview.
    When I click "S.H.I.E.L.D"
    Then I should see the heading "Avengers initiative"
    And I should see the heading "Project Hawaii"
    And I should see the heading "Hellicarrier"
    And I should see the heading "Project 'Captain America'"
    And I should see the heading "Captain America not dead?"
    And I should see the heading "Hellicarrier under attack"
    And I should see the heading "Phil Coulson is down"
    And I should see the heading "Captain America & Avengers"
    And I should see the heading "Project Hawaii case 1"
    And I should see the heading "Phoenix is down"
    And I should see the heading "S.H.I.E.L.D. is infiltrated"
    And I should see the heading "Who is Winter soldier?"
    And I should see the heading "New York under attack"

    # See menu items.
    Then I should see the following collection menu items in the specified order:
      | text               |
      | S.H.I.E.L.D. Home  |
      | About S.H.I.E.L.D. |
      | List of members    |
      | How to apply       |