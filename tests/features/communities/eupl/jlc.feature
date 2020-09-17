@api @eupl
Feature:
  As a product owner of an open source project
  In order to assert whether I can reuse software or data and redistribute it using my favorite licence
  I want to be able to get advice on whether two licences are compatible

  Scenario: Compatibility of licences can be determined using licence compatibility rules
    Given SPDX licences:
      | uri                                | title        | ID           |
      | http://joinup.eu/spdx/GPL-2.0-only | GPL-2.0-only | GPL-2.0-only |
      | http://joinup.eu/spdx/GPL-2.0+     | GPL-2.0+     | GPL-2.0+     |
      | http://joinup.eu/spdx/EUPL-1.1     | EUPL-1.1     | EUPL-1.1     |

    And licences:
      | uri                               | title        | spdx licence | legal type                           |
      | http://joinup.eu/licence/gpl2only | GPL-2.0-only | GPL-2.0-only | For software, Copyleft/Share a.      |
      | http://joinup.eu/licence/gpl2plus | GPL-2.0+     | GPL-2.0+     | GPL, For software, Copyleft/Share a. |
      | http://joinup.eu/licence/eupl11   | EUPL-1.1     | EUPL-1.1     | GPL, For software, Copyleft/Share a. |

    Then the following licences should show the expected compatibility document:
      | use          | redistribute as | document ID  |
      | GPL-2.0-only | GPL-2.0-only    | T01          |
      | GPL-2.0+     | GPL-2.0+        | T01          |
      | EUPL-1.1     | EUPL-1.1        | T01          |
      | GPL-2.0-only | EUPL-1.1        | T02          |
      | GPL-2.0+     | EUPL-1.1        | T02          |
      | GPL-2.0-only | GPL-2.0+        | incompatible |
