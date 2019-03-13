@api @terms
Feature: Joinup should be ADMS-AP compliant.


  Scenario: Validate the entities in the published graph.
    Given users:
      | Username         | E-mail                       |
      | Andre Munson     | andre.munson@example.com     |
      | Branson Winthrop | branson.winthrop@example.com |
    And contacts:
      | name           | email                    | website url            |
      | Jocelyn Bass   | jocelyn.bass@example.com | http://www.example.org |
      | Geoffrey Bryce | geoffrey.bryce           |                        |
    And owner:
      | name       | type                          |
      | Teddy Bass | Non-Governmental Organisation |
    And the following licence:
      | title       | Foo licence       |
      | description | Some nice licence |
      | type        | Attribution       |
    And the following solutions:
      | title        | author           | description    | logo     | banner     | owner      | contact information | creation date   | modification date | documentation | elibrary creation | keywords         | landing page                    | language    | metrics page                    | moderation | policy domain            | related solutions | solution type                  | source code repository        | spatial coverage | status    | translation | webdav creation | webdav url                    | wiki                        | state     | featured | pinned site-wide | pinned in |
      | Early Omega  | Andre Munson     | <p>content</p> | logo.png | banner.jpg | Teddy Bass | Jocelyn Bass        | 2017-11-01T8:00 | 2017-12-01T8:43   | text.pdf      | registered users  | ADMS, validation | http://www.example.com/landing1 | Interlingua | http://www.example.org/metrics1 | no         | EU and European Policies |                   | [ABB15] Service Delivery Model | http://www.example.org/source | Canada           | Completed |             | no              | http://www.example.org/webdav | http://www.example.org/wiki | validated | no       | no               |           |
      | Snake Timely | Branson Winthrop | <p>content</p> | logo.png | banner.jpg | Teddy Bass | Jocelyn Bass        | 2015-03-03T8:00 | 2018-02-14T18:43  | text.pdf      | registered users  |                  | http://www.example.com/landing2 | English     | http://www.example.org/metrics2 | yes        | Demography               | Early Omega       | [ABB13] Business Information   | http://www.example.org/source | Canada           | Completed |             | yes             | http://www.example.org/webdav | http://www.example.org/wiki | validated | no       | no               |           |
    And the following releases:
      | title  | documentation | release number | release notes | creation date    | is version of | state     | status    | spatial coverage | keywords | language             |
      | Omega3 | text.pdf      | 3.0.0          | New 3.0       | 2017-11-11T11:11 | Early Omega   | validated | Completed | Andorra          | food     | Athapascan languages |
    And the following distributions:
      | title      | description                    | creation date    | access url                        | parent | downloads | licence     | format | status            | representation technique |
      | Omega3.zip | The zipped version of Omega 3. | 2017-11-11T11:20 | http://www.example.org/omega3.zip | Omega3 | 232       | Foo licence | HTML   | Under development | Datalog                  |
    And the following collection:
      | title               | Morbid Scattered Microphone    |
      | author              | Andre Munson                   |
      | abstract            | Abstract.                      |
      | description         | Description of the collection. |
      | access url          | http://www.example.com/msm/    |
      | affiliates          | Early Omega, Snake Timely      |
      | logo                | logo.png                       |
      | banner              | banner.jpg                     |
      | contact information | Jocelyn Bass                   |
      | owner               | Teddy Bass                     |
      | creation date       | 2016-07-13T13:00               |
      | modification date   | 2017-06-30T11:27               |
      | closed              | no                             |
      | elibrary creation   | facilitators                   |
      | moderation          | no                             |
      | policy domain       | Demography                     |
      | spatial coverage    | Belgium                        |
      | state               | validated                      |
      | featured            | no                             |
      | pinned site-wide    | no                             |
    Then the ADMS-AP data of the published entities in Joinup is valid
