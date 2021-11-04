@api @group-e
Feature: User API
  In order to manage users programmatic
  As a backend developer
  I need to be able to use the User API

  Scenario: Programmatically create a user
    Given the following user:
      | Username       | Leonardo Da Vinci                            |
      | E-mail         |                                              |
      | Status         | active                                       |
      | First name     | Leonardo                                     |
      | Family name    | di ser Piero da Vinci                        |
      | Photo          | leonardo.jpg                                 |
      | Business title | invention, painting, sculpting, architecture |
      | Organisation   | Verrocchio's workshop                        |
     # @Fixme unimplemented.
     # | Professional domain  |                                              |
     # | Nationality          |                                              |
     # | Social network       |                                              |
    Then I should have a "Leonardo Da Vinci" user
