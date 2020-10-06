@api @group-a
Feature:
  As a product owner of an open source project
  In order to assert whether I can reuse software or data and redistribute it using my favorite licence
  I want to be able to get advice on whether two licences are compatible

  @eupl
  Scenario: Compatibility of licences can be determined using licence compatibility rules
    Given SPDX licences:
      | uri                                    | title            | ID               |
      | http://joinup.eu/spdx/AGPL-3.0-only    | AGPL-3.0-only    | AGPL-3.0-only    |
      | http://joinup.eu/spdx/Apache-2.0       | Apache-2.0       | Apache-2.0       |
      | http://joinup.eu/spdx/CC-BY-ND-4.0     | CC-BY-ND-4.0     | CC-BY-ND-4.0     |
      | http://joinup.eu/spdx/CC-BY-SA-4.0     | CC-BY-SA-4.0     | CC-BY-SA-4.0     |
      | http://joinup.eu/spdx/CECILL-2.0       | CECILL-2.0       | CECILL-2.0       |
      | http://joinup.eu/spdx/CECILL-2.1       | CECILL-2.1       | CECILL-2.1       |
      | http://joinup.eu/spdx/CECILL-C         | CECILL-C         | CECILL-C         |
      | http://joinup.eu/spdx/CPL-1.0          | CPL-1.0          | CPL-1.0          |
      | http://joinup.eu/spdx/EUPL-1.1         | EUPL-1.1         | EUPL-1.1         |
      | http://joinup.eu/spdx/EUPL-1.2         | EUPL-1.2         | EUPL-1.2         |
      | http://joinup.eu/spdx/EPL-2.0          | EPL-2.0          | EPL-2.0          |
      | http://joinup.eu/spdx/EPL-2.1          | EPL-2.1          | EPL-2.1          |
      | http://joinup.eu/spdx/GPL-2.0-only     | GPL-2.0-only     | GPL-2.0-only     |
      | http://joinup.eu/spdx/GPL-2.0+         | GPL-2.0+         | GPL-2.0+         |
      | http://joinup.eu/spdx/GPL-3.0-only     | GPL-3.0-only     | GPL-3.0-only     |
      | http://joinup.eu/spdx/GPL-3.0-or-later | GPL-3.0-or-later | GPL-3.0-or-later |
      | http://joinup.eu/spdx/LGPL-2.1         | LGPL-2.1         | LGPL-2.1         |
      | http://joinup.eu/spdx/LGPL-3.0-only    | LGPL-3.0-only    | LGPL-3.0-only    |
      | http://joinup.eu/spdx/LiLiQ-Rplus-1.1  | LiLiQ-Rplus-1.1  | LiLiQ-Rplus-1.1  |
      | http://joinup.eu/spdx/MIT              | MIT              | MIT              |
      | http://joinup.eu/spdx/MPL-2.0          | MPL-2.0          | MPL-2.0          |
      | http://joinup.eu/spdx/OFL-1.1          | OFL-1.1          | OFL-1.1          |
      | http://joinup.eu/spdx/OSL-3.0          | OSL-3.0          | OSL-3.0          |
      # The following two are non-existing licences tailored to test cases T17 and T18
      # since none of the licences that are included in Joinup at this time match them.
      | http://joinup.eu/spdx/DATA             | DATA             | DATA             |
      | http://joinup.eu/spdx/SOFT             | SOFT             | SOFT             |

    And licences:
      | uri                                   | title            | spdx licence     | legal type                                            |
      | http://joinup.eu/licence/agpl3only    | AGPL-3.0-only    | AGPL-3.0-only    | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/apache2      | Apache-2.0       | Apache-2.0       | Permissive, GPL, For software                         |
      | http://joinup.eu/licence/ccbynd4      | CC-BY-ND-4.0     | CC-BY-ND-4.0     | For data, Copyleft/Share a.                           |
      | http://joinup.eu/licence/ccbysa4      | CC-BY-SA-4.0     | CC-BY-SA-4.0     | For data, Copyleft/Share a.                           |
      | http://joinup.eu/licence/cecill20     | CECILL-2.0       | CECILL-2.0       | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/cecill21     | CECILL-2.1       | CECILL-2.1       | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/cecillc      | CECILL-C         | CECILL-C         | GPL, For software, Lesser copyleft                    |
      | http://joinup.eu/licence/cpl1         | CPL-1.0          | CPL-1.0          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/eupl11       | EUPL-1.1         | EUPL-1.1         | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/eupl12       | EUPL-1.2         | EUPL-1.2         | GPL, For data, For software, Copyleft/Share a.        |
      | http://joinup.eu/licence/epl2         | EPL-2.0          | EPL-2.0          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/epl21        | EPL-2.1          | EPL-2.1          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/gpl2only     | GPL-2.0-only     | GPL-2.0-only     | For software, Copyleft/Share a.                       |
      | http://joinup.eu/licence/gpl2plus     | GPL-2.0+         | GPL-2.0+         | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/gpl3only     | GPL-3.0-only     | GPL-3.0-only     | For software, Copyleft/Share a.                       |
      | http://joinup.eu/licence/gpl3orlater  | GPL-3.0-or-later | GPL-3.0-or-later | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/lgpl21       | LGPL-2.1         | LGPL-2.1         | GPL, For software, Lesser copyleft                    |
      | http://joinup.eu/licence/lgpl3only    | LGPL-3.0-only    | LGPL-3.0-only    | GPL, For software, Lesser copyleft                    |
      | http://joinup.eu/licence/liliqrplus11 | LiLiQ-Rplus-1.1  | LiLiQ-Rplus-1.1  | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/mit          | MIT              | MIT              | Permissive, GPL, For software                         |
      | http://joinup.eu/licence/mpl2         | MPL-2.0          | MPL-2.0          | GPL, For software, Copyleft/Share a., Lesser copyleft |
      | http://joinup.eu/licence/ofl11        | OFL-1.1          | OFL-1.1          |                                                       |
      | http://joinup.eu/licence/osl3         | OSL-3.0          | OSL-3.0          | For software, Copyleft/Share a.                       |
      | http://joinup.eu/licence/data         | DATA             | DATA             | For data                                              |
      | http://joinup.eu/licence/soft         | SOFT             | SOFT             | For software                                          |

    Then the following combination of licences should be described in the compatibility document:
      | use              | redistribute as  | document ID |
      | AGPL-3.0-only    | AGPL-3.0-only    | T01         |
      | EUPL-1.1         | EUPL-1.1         | T01         |
      | EUPL-1.2         | EUPL-1.2         | T01         |
      | GPL-2.0-only     | GPL-2.0-only     | T01         |
      | GPL-2.0+         | GPL-2.0+         | T01         |
      | GPL-3.0-only     | GPL-3.0-only     | T01         |
      | GPL-3.0-or-later | GPL-3.0-or-later | T01         |
      | GPL-2.0-only     | EUPL-1.1         | T02         |
      | GPL-2.0+         | EUPL-1.1         | T02         |
      | GPL-3.0-only     | EUPL-1.1         | T03         |
      | GPL-3.0-or-later | EUPL-1.1         | T03         |
      | AGPL-3.0-only    | EUPL-1.1         | T03         |
      | GPL-2.0-only     | EUPL-1.2         | T04         |
      | GPL-2.0+         | EUPL-1.2         | T04         |
      | GPL-3.0-only     | EUPL-1.2         | T04         |
      | GPL-3.0-or-later | EUPL-1.2         | T04         |
      | AGPL-3.0-only    | EUPL-1.2         | T04         |
      | GPL-2.0-only     | GPL-3.0-only     | T05         |
      | GPL-3.0-only     | GPL-2.0-only     | T05         |
      | EUPL-1.1         | EUPL-1.2         | T06         |
      | EUPL-1.2         | EUPL-1.1         | T06         |
      | AGPL-3.0-only    | Apache-2.0       | T07         |
      | GPL-2.0+         | Apache-2.0       | T07         |
      | GPL-2.0-only     | Apache-2.0       | T07         |
      | GPL-3.0-only     | Apache-2.0       | T07         |
      | GPL-3.0-or-later | Apache-2.0       | T07         |
      | AGPL-3.0-only    | MIT              | T07         |
      | GPL-2.0+         | MIT              | T07         |
      | GPL-2.0-only     | MIT              | T07         |
      | GPL-3.0-only     | MIT              | T07         |
      | GPL-3.0-or-later | MIT              | T07         |
      | EUPL-1.1         | CECILL-2.0       | T08         |
      | EUPL-1.1         | CECILL-2.1       | T08         |
      | EUPL-1.1         | CPL-1.0          | T08         |
      | EUPL-1.1         | EPL-2.0          | T08         |
      | EUPL-1.1         | EPL-2.1          | T08         |
      | EUPL-1.1         | GPL-2.0-only     | T08         |
      | EUPL-1.1         | OSL-3.0          | T08         |
      | EUPL-1.1         | AGPL-3.0-only    | T09         |
      | EUPL-1.1         | GPL-3.0-only     | T09         |
      | EUPL-1.1         | GPL-3.0-or-later | T09         |
      | EUPL-1.2         | AGPL-3.0-only    | T10         |
      | EUPL-1.2         | CC-BY-SA-4.0     | T10         |
      | EUPL-1.2         | CECILL-2.0       | T10         |
      | EUPL-1.2         | CECILL-2.1       | T10         |
      | EUPL-1.2         | CPL-1.0          | T10         |
      | EUPL-1.2         | EPL-2.0          | T10         |
      | EUPL-1.2         | EPL-2.1          | T10         |
      | EUPL-1.2         | GPL-2.0+         | T10         |
      | EUPL-1.2         | GPL-2.0-only     | T10         |
      | EUPL-1.2         | GPL-3.0-only     | T10         |
      | EUPL-1.2         | GPL-3.0-or-later | T10         |
      | EUPL-1.2         | LGPL-2.1         | T10         |
      | EUPL-1.2         | LGPL-3.0-only    | T10         |
      | EUPL-1.2         | MPL-2.0          | T10         |
      | EUPL-1.2         | OSL-3.0          | T10         |
      | LiLiQ-Rplus-1.1  | EUPL-1.1         | T11         |
      | CECILL-2.1       | EUPL-1.1         | T11         |
      | LiLiQ-Rplus-1.1  | EUPL-1.2         | T11         |
      | CECILL-2.1       | EUPL-1.2         | T11         |
      | Apache-2.0       | AGPL-3.0-only    | T12         |
      | Apache-2.0       | CC-BY-ND-4.0     | T12         |
      | Apache-2.0       | CC-BY-SA-4.0     | T12         |
      | Apache-2.0       | CECILL-2.0       | T12         |
      | Apache-2.0       | CECILL-2.1       | T12         |
      | Apache-2.0       | CPL-1.0          | T12         |
      | Apache-2.0       | EUPL-1.1         | T12         |
      | Apache-2.0       | EUPL-1.2         | T12         |
      | Apache-2.0       | EPL-2.0          | T12         |
      | Apache-2.0       | EPL-2.1          | T12         |
      | Apache-2.0       | GPL-2.0-only     | T12         |
      | Apache-2.0       | GPL-2.0+         | T12         |
      | Apache-2.0       | GPL-3.0-only     | T12         |
      | Apache-2.0       | GPL-3.0-or-later | T12         |
      | Apache-2.0       | LGPL-2.1         | T12         |
      | Apache-2.0       | LGPL-3.0-only    | T12         |
      | Apache-2.0       | LiLiQ-Rplus-1.1  | T12         |
      | Apache-2.0       | MIT              | T12         |
      | Apache-2.0       | MPL-2.0          | T12         |
      | Apache-2.0       | OSL-3.0          | T12         |
      | MIT              | AGPL-3.0-only    | T12         |
      | MIT              | Apache-2.0       | T12         |
      | MIT              | CC-BY-ND-4.0     | T12         |
      | MIT              | CC-BY-SA-4.0     | T12         |
      | MIT              | CECILL-2.0       | T12         |
      | MIT              | CECILL-2.1       | T12         |
      | MIT              | CPL-1.0          | T12         |
      | MIT              | EUPL-1.1         | T12         |
      | MIT              | EUPL-1.2         | T12         |
      | MIT              | EPL-2.0          | T12         |
      | MIT              | EPL-2.1          | T12         |
      | MIT              | GPL-2.0-only     | T12         |
      | MIT              | GPL-2.0+         | T12         |
      | MIT              | GPL-3.0-only     | T12         |
      | MIT              | GPL-3.0-or-later | T12         |
      | MIT              | LGPL-2.1         | T12         |
      | MIT              | LGPL-3.0-only    | T12         |
      | MIT              | LiLiQ-Rplus-1.1  | T12         |
      | MIT              | MPL-2.0          | T12         |
      | MIT              | OSL-3.0          | T12         |
      | CECILL-2.1       | GPL-2.0-only     | T13         |
      | CECILL-2.1       | GPL-2.0+         | T13         |
      | CECILL-2.1       | GPL-3.0-only     | T13         |
      | CECILL-2.1       | GPL-3.0-or-later | T13         |
      | CECILL-2.1       | AGPL-3.0-only    | T13         |
      | CC-BY-ND-4.0     | Apache-2.0       | T14         |
      | CC-BY-SA-4.0     | Apache-2.0       | T14         |
      | CECILL-2.0       | Apache-2.0       | T14         |
      | CECILL-2.1       | Apache-2.0       | T14         |
      | CPL-1.0          | Apache-2.0       | T14         |
      | EUPL-1.1         | Apache-2.0       | T14         |
      | EUPL-1.2         | Apache-2.0       | T14         |
      | EPL-2.0          | Apache-2.0       | T14         |
      | EPL-2.1          | Apache-2.0       | T14         |
      | LGPL-2.1         | Apache-2.0       | T14         |
      | LGPL-3.0-only    | Apache-2.0       | T14         |
      | LiLiQ-Rplus-1.1  | Apache-2.0       | T14         |
      | MPL-2.0          | Apache-2.0       | T14         |
      | OSL-3.0          | Apache-2.0       | T14         |
      | CC-BY-ND-4.0     | MIT              | T14         |
      | CC-BY-SA-4.0     | MIT              | T14         |
      | CECILL-2.0       | MIT              | T14         |
      | CECILL-2.1       | MIT              | T14         |
      | CPL-1.0          | MIT              | T14         |
      | EUPL-1.1         | MIT              | T14         |
      | EUPL-1.2         | MIT              | T14         |
      | EPL-2.0          | MIT              | T14         |
      | EPL-2.1          | MIT              | T14         |
      | LGPL-2.1         | MIT              | T14         |
      | LGPL-3.0-only    | MIT              | T14         |
      | LiLiQ-Rplus-1.1  | MIT              | T14         |
      | MPL-2.0          | MIT              | T14         |
      | OSL-3.0          | MIT              | T14         |
      | CC-BY-ND-4.0     | EPL-2.0          | T15         |
      | CC-BY-SA-4.0     | EPL-2.0          | T15         |
      | CECILL-2.0       | EPL-2.0          | T15         |
      | CECILL-2.1       | EPL-2.0          | T15         |
      | CPL-1.0          | EPL-2.0          | T15         |
      | EPL-2.1          | EPL-2.0          | T15         |
      | LGPL-2.1         | EPL-2.0          | T15         |
      | LGPL-3.0-only    | EPL-2.0          | T15         |
      | LiLiQ-Rplus-1.1  | EPL-2.0          | T15         |
      | MPL-2.0          | EPL-2.0          | T15         |
      | OSL-3.0          | EPL-2.0          | T15         |
      | CC-BY-ND-4.0     | OSL-3.0          | T15         |
      | CC-BY-SA-4.0     | OSL-3.0          | T15         |
      | CECILL-2.0       | OSL-3.0          | T15         |
      | CECILL-2.1       | OSL-3.0          | T15         |
      | CPL-1.0          | OSL-3.0          | T15         |
      | EPL-2.0          | OSL-3.0          | T15         |
      | EPL-2.1          | OSL-3.0          | T15         |
      | LGPL-2.1         | OSL-3.0          | T15         |
      | LGPL-3.0-only    | OSL-3.0          | T15         |
      | LiLiQ-Rplus-1.1  | OSL-3.0          | T15         |
      | MPL-2.0          | OSL-3.0          | T15         |
      | CC-BY-SA-4.0     | CECILL-C         | T16         |
      | CECILL-2.0       | CECILL-C         | T16         |
      | CECILL-2.1       | CECILL-C         | T16         |
      | CPL-1.0          | CECILL-C         | T16         |
      | EUPL-1.1         | CECILL-C         | T16         |
      | EUPL-1.2         | CECILL-C         | T16         |
      | EPL-2.0          | CECILL-C         | T16         |
      | EPL-2.1          | CECILL-C         | T16         |
      | LiLiQ-Rplus-1.1  | CECILL-C         | T16         |
      | OSL-3.0          | CECILL-C         | T16         |
      | SOFT             | DATA             | T17         |
      | DATA             | SOFT             | T18         |
      | CC-BY-ND-4.0     | OFL-1.1          | T99         |
      | CECILL-C         | LGPL-2.1         | T99         |
      | CECILL-C         | LGPL-3.0-only    | T99         |
      | LGPL-2.1         | CECILL-C         | T99         |
      | LGPL-3.0-only    | CECILL-C         | T99         |
      | OFL-1.1          | CC-BY-ND-4.0     | T99         |

  Scenario: Moderators can edit licence compatibility documents
    # The compatibility documents overview should be accessible through the toolbar.
    Given I am logged in as a moderator
    When I click "Compatibility documents" in the "Administration toolbar" region
    Then I should see the heading "Compatibility documents"

    # The compatibility documents should exist and have a default text.
    When I click "Edit" in the "Content" region
    Then I should see the heading "Edit T01"
    And I should see the text "Compatibility document comparing @use-licence with @redistribute-as-licence."

    # The document should be editable.
    When I fill in "Description" with "In case the two components are not merged, each component keeps its primary licence: @use-licence or @redistribute-as-licence."
    And I press "Save"
    Then I should see the success message "The compatibility document T01 has been updated."
    When I click "Edit"
    Then I should see the text "In case the two components are not merged, each component keeps its primary licence: @use-licence or @redistribute-as-licence."

    # The compatibility documents are populated automatically when visiting the
    # overview. Clean them up again.
    Then all compatibility documents are cleaned up

  @eupl
  Scenario: Get information about how a project can be redistributed under another licence
    Given SPDX licences:
      | uri                            | title    | ID       |
      | http://joinup.eu/spdx/CECILL-C | CECILL-C | CECILL-C |
      | http://joinup.eu/spdx/EUPL-1.2 | EUPL-1.2 | EUPL-1.2 |
      | http://joinup.eu/spdx/LGPL-2.1 | LGPL-2.1 | LGPL-2.1 |

    And licences:
      | uri                              | title    | spdx licence | legal type                                     |
      | http://joinup.eu/licence/cecillc | CECILL-C | CECILL-C     | GPL, For software, Lesser copyleft             |
      | http://joinup.eu/licence/eupl12  | EUPL-1.2 | EUPL-1.2     | GPL, For data, For software, Copyleft/Share a. |
      | http://joinup.eu/licence/lgpl21  | LGPL-2.1 | LGPL-2.1     | GPL, For software, Lesser copyleft             |

    And compatibility documents:
      | id  | description                                                                                                                                                                    |
      | T01 | Freedom for using and re-distributing is a basic common characteristic of all open licences.                                                                                   |
      | T10 | The hypothetical risk of 'viral effect' from @redistribute-as-licence to the code covered by @use-licence in the case of dynamic or even static linking will not be validated. |
      | T16 | You have to check if the text of @use-licence has expressly mentioned @redistribute-as-licence as compatible.                                                                  |
      | T99 | @use-licence is not compatible with @redistribute-as-licence.                                                                                                                  |

    When I visit "licence/compatibility-check/EUPL-1.2/EUPL-1.2"
    Then I should see the heading "Can EUPL-1.2 be redistributed as EUPL-1.2?"
    And I should see the text "Freedom for using and re-distributing is a basic common characteristic of all open licences."
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    When I visit "licence/compatibility-check/LGPL-2.1/LGPL-2.1"
    Then I should see the heading "Can LGPL-2.1 be redistributed as LGPL-2.1?"
    And I should see the text "Freedom for using and re-distributing is a basic common characteristic of all open licences."
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    When I visit "licence/compatibility-check/EUPL-1.2/LGPL-2.1"
    Then I should see the heading "Can EUPL-1.2 be redistributed as LGPL-2.1?"
    And I should see the text "The hypothetical risk of 'viral effect' from LGPL-2.1 to the code covered by EUPL-1.2 in the case of dynamic or even static linking will not be validated."
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    When I visit "licence/compatibility-check/EUPL-1.2/CECILL-C"
    Then I should see the heading "Can EUPL-1.2 be redistributed as CECILL-C?"
    And I should see the text "You have to check if the text of EUPL-1.2 has expressly mentioned CECILL-C as compatible."
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    When I visit "licence/compatibility-check/LGPL-2.1/CECILL-C"
    Then I should see the heading "Can LGPL-2.1 be redistributed as CECILL-C?"
    And I should see the text "LGPL-2.1 is not compatible with CECILL-C"
    And the page should be cacheable
    When I reload the page
    Then the page should be cached

    When I go to "licence/compatibility-check/LPG-2.1/DIESEL-C"
    Then the response status code should be 404
    And I should see the heading "Page not found"
    And the page should be cacheable
    When I reload the page
    Then the page should be cached
