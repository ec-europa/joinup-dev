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
