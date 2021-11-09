@api @group-e
Feature: About this solution
  In order to expose basic information about my solutions
  As a solution owner or solution facilitator
  I need to be able to sum up information in an "About" page

  @terms
  Scenario: About page
    Given the following contacts:
      | name                    | email                        | Website URL                     |
      | Ariel Lucile            | ariel@nova.dk                | http://nova.dk, http://nova.com |
      | Maiken Bine, Peer Milla | maiken@nova.dk, peer@nova.dk | https://innovation.nova.org     |
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
      | title               | Size exclusion chromatography                     |
      | description         | Separating molecules by size.                     |
      | logo                | logo.png                                          |
      | banner              | banner.jpg                                        |
      | contact information | Ariel Lucile, "Maiken Bine, Peer Milla"           |
      | owner               | Nova Pharmaceuticals, Senatier                    |
      | state               | validated                                         |
      | documentation       | text.pdf                                          |
      | language            | Italian, Kallawaya                                |
      | topic               | Demography, E-inclusion                           |
      | related solutions   | Gel, Polymer, Protein                             |
      | solution type       | Non-binding Instrument, Service Discovery Service |
      | spatial coverage    | Italy, Egypt                                      |
      | status              | Under development                                 |
      | collection          | Monoclonal Antibody Development                   |

    # The link to the about page should be visible on the solution homepage.
    When I am not logged in
    And I go to the homepage of the "Size exclusion chromatography" solution
    Then I should see the link "About"

    # All the public information from the basic fields should be visible on the
    # about page.
    When I click "About"

    # Clean URLs should be applied to the "About" subpage.
    Then I should be on "/collection/monoclonal-antibody-development/solution/size-exclusion-chromatography/about"

    # The description.
    Then I should see the text "Separating molecules by size."

    # The grey area for owner and contact information.
    And I should see the text "Owner/Contact Information"

    # Multiple owners.
    And I should see the text "Owner"
    And I should see the following lines of text:
      | Nova Pharmaceuticals |
      | Senatier             |

    # Multiple instances of Contact information, each potentially having
    # multiple names, e-mail addresses and websites.
    And I should see the text "Contact information"
    And I should see the following lines of text:
      | Ariel Lucile |
      | Maiken Bine  |
      | Peer Milla   |
    And I should see the text "E-mail address"
    And I should see the text "Website URL"
    And I should see the following links:
      | ariel@nova.dk               |
      | maiken@nova.dk              |
      | peer@nova.dk                |
      | http://nova.com             |
      | http://nova.dk              |
      | https://innovation.nova.org |

    # Categorisation grey area.
    And I should see the text "Categorisation"
    And I should see the following lines of text:
      | Non-binding instrument    |
      | Service Discovery Service |

    And I should see the text "Status"
    And I should see the text "Under development"

    And I should see the text "Languages"
    And I should see the following lines of text:
      | Kallawaya |
      | Italian   |

    # Moderation grey area.
    And I should see the text "Moderation"

    # The rest of the fields should not be seen at the moment.
    # Multiple spatial coverage entries.
    And I should not see the following lines of text:
      | Egypt |
      | Italy |

    # Multiple topics.
    And I should see the text "Demography" in the "Header" region
    And I should see the text "E-inclusion" in the "Header" region
    But I should not see the text "Demography" in the "Content" region
    And I should not see the text "E-inclusion" in the "Content" region

    # Multiple related solutions.
    And I should not see the following lines of text:
      | Gel     |
      | Polymer |

    # A related solution which is not yet approved should not be visible.
    And I should not see the text "Protein"
