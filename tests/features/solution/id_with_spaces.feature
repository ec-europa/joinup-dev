@api @group-d
Feature: Solution API
  In order to be able to use solutions with spaces in their uris
  As a backend developer
  I need to be able to store them

  Scenario: Programmatically create a solution
    Given the following solution:
      | uri              | http://example.com/this%20has%20spaces |
      | title            | Solution with spaces                   |
      | description      | A sample solution                      |
      | logo             | logo.png                               |
      | banner           | banner.jpg                             |
      | documentation    | text.pdf                               |
      | content creation | registered users                       |
      | landing page     | http://foo-example.com/landing         |
      | webdav creation  | no                                     |
      | webdav url       | http://joinup.eu/solution/foo/webdav   |
      | wiki             | http://example.wiki/foobar/wiki        |
      | state            | validated                              |
    And the following solution:
      | uri              | http://www.it-planungsrat.de/DE/Projekte/Koordinierungsprojekte/OsiP/Online_Sicherheitspr%C3%BCfung.html |
      | title            | Solution with unicode                                                                                    |
      | description      | A sample solution                                                                                        |
      | logo             | logo.png                                                                                                 |
      | banner           | banner.jpg                                                                                               |
      | documentation    | text.pdf                                                                                                 |
      | content creation | registered users                                                                                         |
      | landing page     | http://foo-example.com/landing                                                                           |
      | webdav creation  | no                                                                                                       |
      | webdav url       | http://joinup.eu/solution/foo/webdav                                                                     |
      | wiki             | http://example.wiki/foobar/wiki                                                                          |
      | state            | validated                                                                                                |
