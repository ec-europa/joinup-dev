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

    # The group content digest should contain the content posted in the
    # collection and the solution.
    Then the daily group content subscription digest for jpluminet should match the following messages:
      | Active galactic nuclei |
      | Doppler effect         |
      | Effective potential    |
      | The periastron         |
      | Light diffusion        |
      | Distant observers      |
      | Deflected rays         |
      | Newtonian context      |

    # Check that the message is formatted correctly. It should contain the
    # groups in alphabetical order, underneath which the group content is shown,
    # also in alphabetical order.
    Given all message digests have been delivered
    Then the group content subscription digest sent to jpluminet contains the following sections:
      | title                  |
      | Black hole imaging     |
      | Active galactic nuclei |
      | Doppler effect         |
      | Effective potential    |
      | The periastron         |
      | Bolometric appearance  |
      | Distant observers      |
      | Newtonian context      |
      | Null geodesics         |
      | Deflected rays         |
      | Light diffusion        |
