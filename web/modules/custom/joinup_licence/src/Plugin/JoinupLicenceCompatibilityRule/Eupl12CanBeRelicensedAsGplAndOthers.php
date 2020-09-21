<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T10 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=EUPL-1.2
 * - <Licence-B>: SPDX=GPL-2.0-only OR GPL-2.0+ OR GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only OR LGPL-2.1 OR LGPL-3.0-only OR OSL-3.0 OR CECILL-2.0 OR CECILL-2.1 OR EPL-2.0 OR EPL-2.1 or CPL-1.0 OR  MPL-2.0 OR CC-BY-SA-4.0
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "eupl_1_2_can_be_relicensed_as_gpl_and_others",
 *   document_id = "T10",
 *   weight = 1000
 * )
 */
class Eupl12CanBeRelicensedAsGplAndOthers extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'EUPL-1.2',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'CC-BY-SA-4.0',
      'CECILL-2.0',
      'CECILL-2.1',
      'CPL-1.0',
      'EPL-2.0',
      'EPL-2.1',
      'GPL-2.0+',
      'GPL-2.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
      'LGPL-2.1',
      'LGPL-3.0-only',
      'MPL-2.0',
      'OSL-3.0',
    ],
  ];

}
