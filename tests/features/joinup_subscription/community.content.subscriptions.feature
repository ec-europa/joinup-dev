@api @group-b
Feature: Subscribing to community content in collections and solutions
  As an avid fan of Joinup
  I want to receive a periodic digest listing newly published content
  So that I can stay informed about everything

  Background:
    Given the following collections:
      | title              | state     |
      | Black hole imaging | validated |
    And the following solutions:
      | title                 | state     | collection         |
      | Null geodesics        | validated | Black hole imaging |
      | Bolometric appearance | validated | Black hole imaging |
    And users:
      | Username  | E-mail               | First name  | Family name | Notification frequency |
      | jpluminet | jpluminet@example.fr | Jean-Pierre | Luminet     | daily                  |
      | junfukue  | jun.fukue@example.jp | Jun         | Fukue       | weekly                 |
    And the following collection user memberships:
      | collection         | user      | roles |
      | Black hole imaging | jpluminet |       |
    And the following solution user memberships:
      | solution              | user      | roles |
      | Null geodesics        | jpluminet |       |
      | Bolometric appearance | jpluminet |       |
    And the following collection content subscriptions:
      | collection         | user      | subscriptions                               |
      | Black hole imaging | jpluminet | discussion, document, event, news, solution |
    And the following solution content subscriptions:
      | solution              | user      | subscriptions                     |
      | Null geodesics        | jpluminet | discussion, document, event, news |
      | Bolometric appearance | jpluminet | discussion, document, event, news |

    And all message digests have been delivered
    And the mail collector cache is empty

  @email
  Scenario: Receive a digest of community content that is published
    Given discussion content:
      | title                  | body                               | collection         | solution       | state     | author   |
      | Active galactic nuclei | A thin relativistic accretion disk | Black hole imaging |                | validated | junfukue |
      | Light diffusion        | Photons emitted at constant radius |                    | Null geodesics | validated | junfukue |
    And document content:
      | title             | body                            | collection         | solution              | state     | author   |
      | Doppler effect    | Caused by disk rotation         | Black hole imaging |                       | validated | junfukue |
      | Distant observers | Distribution of bolometric flux |                    | Bolometric appearance | validated | junfukue |
    And event content:
      | title               | body                 | collection         | solution       | state     | author   | start date          | end date            |
      | Effective potential | Schwarzschild metric | Black hole imaging |                | validated | junfukue | 2019-11-28T11:12:13 | 2019-11-28T11:12:13 |
      | Deflected rays      | Marginally trapped   |                    | Null geodesics | validated | junfukue | 2019-12-05T12:00:00 | 2019-12-15T12:00:00 |
    And news content:
      | title             | body                       | collection         | solution              | state     | author |
      | The periastron    | Jacobian elliptic integral | Black hole imaging |                       | validated | bisera |
      | Newtonian context | Projecting ellipses        |                    | Bolometric appearance | validated | hristo |

    # The collection digest should only contain the content posted directly in
    # the collection itself.
    Then the daily group content subscription digest for jpluminet should match the following messages:
      | Active galactic nuclei |
      | Doppler effect         |
      | Effective potential    |
      | The periastron         |
    # The solution digest should only contain content posted in solutions.
    Then the daily group content subscription digest for jpluminet should match the following messages:
      | Light diffusion   |
      | Distant observers |
      | Deflected rays    |
      | Newtonian context |

    # Check that the messages are formatted correctly.
    Given all message digests have been delivered
    Then the group content subscription digest sent to jpluminet contains the following sections:
      | title                  |
      | Black hole imaging     |
      | Active galactic nuclei |
      | Doppler effect         |
      | Effective potential    |
      | The periastron         |
    And the email sent to "jpluminet" with subject "Joinup: Daily Collection digest message" contains the following lines of text:
      | text                                                   |
      | New content published in Collection Black hole imaging |
    And the email sent to "jpluminet" with subject "Joinup: Daily Collection digest message" should not contain the following lines of text:
      | text                                                    |
      | New content published in Solution Null geodesics        |
      | New content published in Solution Bolometric appearance |

    And the group content subscription digest sent to jpluminet contains the following sections:
      | title                 |
      | Null geodesics        |
      | Light diffusion       |
      | Deflected rays        |
      | Bolometric appearance |
      | Distant observers     |
      | Newtonian context     |
    And the email sent to "jpluminet" with subject "Joinup: Daily Solution digest message" contains the following lines of text:
      | text                                                    |
      | New content published in Solution Null geodesics        |
      | New content published in Solution Bolometric appearance |
    And the email sent to "jpluminet" with subject "Joinup: Daily Solution digest message" should not contain the following lines of text:
      | text                                                   |
      | New content published in Collection Black hole imaging |

