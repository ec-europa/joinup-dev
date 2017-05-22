@api
Feature: About this solution
  In order to expose basic information about my solutions
  As a solution owner or solution facilitator
  I need to be able to sum up information in an "About" page

  Scenario: About page
    Given the following contacts:
      | name                      | email                        | Website URL                     |
      | Ariel Lucile              | ariel@nova.dk                | http://nova.dk, http://nova.com |
      | Maiken Bine, Peer Milla   | maiken@nova.dk, peer@nova.dk | https://innovation.nova.org     |
    And owners:
      | name                 | type                         |
      | Nova Pharmaceuticals | Company, Industry consortium |
      | Senatier             | National authority           |
    And collection:
      | title | Monoclonal Antibody Development |
      | state | validated                       |
    And solutions:
      | title   | state     |
      | Gel     | validated |
      | Polymer | validated |
      | Protein | proposed  |
    And solution:
      | title               | Size exclusion chromatography                                       |
      | description         | Separating molecules by size.                                       |
      | logo                | logo.png                                                            |
      | banner              | banner.jpg                                                          |
      | contact information | Ariel Lucile, "Maiken Bine, Peer Milla"                             |
      | owner               | Nova Pharmaceuticals, Senatier                                      |
      | state               | validated                                                           |
      | documentation       | text.pdf                                                            |
      | language            | Italian, Kallawaya                                                  |
      | policy domain       | Whales protection, E-identity                                       |
      | related solutions   | Gel, Polymer, Protein                                               |
      | solution type       | [ABB113] Non-binding Instrument, [ABB159] Service Discovery Service |
      | spatial coverage    | Netherlands Antilles, Egypt                                         |
      | status              | Under development                                                   |
      | collection          | Monoclonal Antibody Development                                     |

    # The link to the about page should be visible on the solution homepage.
    When I am not logged in
    And I go to the homepage of the "Size exclusion chromatography" solution
    Then I should see the link "About"

    # All the public information from the basic fields should be visible on the
    # about page.
    When I click "About"

    # Multiple instances of Contact information, each potentially having
    # multiple names, e-mail addresses and websites.
    Then I should see the following lines of text:
    | Ariel Lucile |
    | Maiken Bine  |
    | Peer Milla   |
    And I should see the following links:
    | ariel@nova.dk               |
    | maiken@nova.dk              |
    | peer@nova.dk                |
    | http://nova.com             |
    | http://nova.dk              |
    | https://innovation.nova.org |

    # Multiple owners.
    And I should see the following lines of text:
    | Nova Pharmaceuticals |
    | Senatier             |

    # Multiple spatial coverage entries.
    And I should see the following lines of text:
    | Egypt                |
    | Netherlands Antilles |

    # Multiple solution types.
    And I should see the following lines of text:
    | [ABB113] Non-binding instrument    |
    | [ABB159] Service Discovery Service |

    # Solution status.
    And I should see the text "Under development"

    # Multiple languages.
    And I should see the following lines of text:
    | Kallawaya |
    | Italian   |

    # Multiple policy domains.
    And I should see the following lines of text:
    | Whales protection |
    | E-identity        |

    # Multiple related solutions.
    And I should see the following lines of text:
    | Gel     |
    | Polymer |

    # A related solution which is not yet approved should not be visible.
    And I should not see the text "Protein"
