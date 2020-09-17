@api @eupl
Feature:
  As a product owner of an open source project
  In order to assert whether I can reuse software or data and redistribute it using my favorite licence
  I want to be able to get advice on whether two licences are compatible

  Scenario: Compatibility of licences can be determined using licence compatibility rules
    Given SPDX licences:
      | uri                                    | title            | ID               |
      | http://joinup.eu/spdx/AGPL-3.0-only    | AGPL-3.0-only    | AGPL-3.0-only    |
      | http://joinup.eu/spdx/Apache-2.0       | Apache-2.0       | Apache-2.0       |
      | http://joinup.eu/spdx/CC-BY-ND-4.0     | CC-BY-ND-4.0     | CC-BY-ND-4.0     |
      | http://joinup.eu/spdx/CC-BY-SA-4.0     | CC-BY-SA-4.0     | CC-BY-SA-4.0     |
      | http://joinup.eu/spdx/CECILL-2.0       | CECILL-2.0       | CECILL-2.0       |
      | http://joinup.eu/spdx/CECILL-2.1       | CECILL-2.1       | CECILL-2.1       |
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
      | http://joinup.eu/spdx/MPL-2.0          | MPL-2.0          | MPL-2.0          |
      | http://joinup.eu/spdx/OSL-3.0          | OSL-3.0          | OSL-3.0          |

    And licences:
      | uri                                  | title            | spdx licence     | legal type                                            |
      | http://joinup.eu/licence/agpl3only   | AGPL-3.0-only    | AGPL-3.0-only    | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/apache2     | Apache-2.0       | Apache-2.0       | Permissive, GPL, For software                         |
      | http://joinup.eu/licence/ccbynd4     | CC-BY-ND-4.0     | CC-BY-ND-4.0     | For data, Copyleft/Share a.                           |
      | http://joinup.eu/licence/ccbysa4     | CC-BY-SA-4.0     | CC-BY-SA-4.0     | For data, Copyleft/Share a.                           |
      | http://joinup.eu/licence/cecill20    | CECILL-2.0       | CECILL-2.0       | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/cecill21    | CECILL-2.1       | CECILL-2.1       | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/cpl1        | CPL-1.0          | CPL-1.0          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/eupl11      | EUPL-1.1         | EUPL-1.1         | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/eupl12      | EUPL-1.2         | EUPL-1.2         | GPL, For data, For software, Copyleft/Share a.        |
      | http://joinup.eu/licence/epl2        | EPL-2.0          | EPL-2.0          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/epl21       | EPL-2.1          | EPL-2.1          | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/gpl2only    | GPL-2.0-only     | GPL-2.0-only     | For software, Copyleft/Share a.                       |
      | http://joinup.eu/licence/gpl2plus    | GPL-2.0+         | GPL-2.0+         | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/gpl3only    | GPL-3.0-only     | GPL-3.0-only     | For software, Copyleft/Share a.                       |
      | http://joinup.eu/licence/gpl3orlater | GPL-3.0-or-later | GPL-3.0-or-later | GPL, For software, Copyleft/Share a.                  |
      | http://joinup.eu/licence/lgpl21      | LGPL-2.1         | LGPL-2.1         | GPL, For software, Lesser copyleft                    |
      | http://joinup.eu/licence/lgpl3only   | LGPL-3.0-only    | LGPL-3.0-only    | GPL, For software, Lesser copyleft                    |
      | http://joinup.eu/licence/mpl2        | MPL-2.0          | MPL-2.0          | GPL, For software, Copyleft/Share a., Lesser copyleft |
      | http://joinup.eu/licence/osl3        | OSL-3.0          | OSL-3.0          | For software, Copyleft/Share a.                       |

    Then the following licences should show the expected compatibility document:
      | use              | redistribute as  | document ID  |
      | AGPL-3.0-only    | AGPL-3.0-only    | T01          |
      | EUPL-1.1         | EUPL-1.1         | T01          |
      | EUPL-1.2         | EUPL-1.2         | T01          |
      | GPL-2.0-only     | GPL-2.0-only     | T01          |
      | GPL-2.0+         | GPL-2.0+         | T01          |
      | GPL-3.0-only     | GPL-3.0-only     | T01          |
      | GPL-3.0-or-later | GPL-3.0-or-later | T01          |
      | GPL-2.0-only     | EUPL-1.1         | T02          |
      | GPL-2.0+         | EUPL-1.1         | T02          |
      | GPL-3.0-only     | EUPL-1.1         | T03          |
      | GPL-3.0-or-later | EUPL-1.1         | T03          |
      | AGPL-3.0-only    | EUPL-1.1         | T03          |
      | GPL-2.0-only     | EUPL-1.2         | T04          |
      | GPL-2.0+         | EUPL-1.2         | T04          |
      | GPL-3.0-only     | EUPL-1.2         | T04          |
      | GPL-3.0-or-later | EUPL-1.2         | T04          |
      | AGPL-3.0-only    | EUPL-1.2         | T04          |
      | GPL-2.0-only     | GPL-3.0-only     | T05          |
      | GPL-3.0-only     | GPL-2.0-only     | T05          |
      | EUPL-1.1         | EUPL-1.2         | T06          |
      | EUPL-1.2         | EUPL-1.1         | T06          |
      | GPL-2.0-only     | Apache-2.0       | T07          |
      | GPL-2.0+         | Apache-2.0       | T07          |
      | GPL-3.0-only     | Apache-2.0       | T07          |
      | GPL-3.0-or-later | Apache-2.0       | T07          |
      | AGPL-3.0-only    | Apache-2.0       | T07          |
      | EUPL-1.1         | CECILL-2.0       | T08          |
      | EUPL-1.1         | CECILL-2.1       | T08          |
      | EUPL-1.1         | CPL-1.0          | T08          |
      | EUPL-1.1         | EPL-2.0          | T08          |
      | EUPL-1.1         | EPL-2.1          | T08          |
      | EUPL-1.1         | GPL-2.0-only     | T08          |
      | EUPL-1.1         | OSL-3.0          | T08          |
      | EUPL-1.1         | AGPL-3.0-only    | T09          |
      | EUPL-1.1         | GPL-3.0-only     | T09          |
      | EUPL-1.1         | GPL-3.0-or-later | T09          |
      | EUPL-1.2         | AGPL-3.0-only    | T10          |
      | EUPL-1.2         | CC-BY-SA-4.0     | T10          |
      | EUPL-1.2         | CECILL-2.0       | T10          |
      | EUPL-1.2         | CECILL-2.1       | T10          |
      | EUPL-1.2         | CPL-1.0          | T10          |
      | EUPL-1.2         | EPL-2.0          | T10          |
      | EUPL-1.2         | EPL-2.1          | T10          |
      | EUPL-1.2         | GPL-2.0+         | T10          |
      | EUPL-1.2         | GPL-2.0-only     | T10          |
      | EUPL-1.2         | GPL-3.0-only     | T10          |
      | EUPL-1.2         | GPL-3.0-or-later | T10          |
      | EUPL-1.2         | LGPL-2.1         | T10          |
      | EUPL-1.2         | LGPL-3.0-only    | T10          |
      | EUPL-1.2         | MPL-2.0          | T10          |
      | EUPL-1.2         | OSL-3.0          | T10          |
      | CC-BY-ND-4.0     | Apache-2.0       | incompatible |
