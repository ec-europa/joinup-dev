<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T06 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=EUPL-1.1 or SPDX=EUPL-1.2
 * - <Licence-B>: SPDX=EUPL-1.1 or SPDX=EUPL-1.2
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "eupl_1_1_and_eupl_1_2_are_compatible",
 *   document_id = "T06",
 *   weight = 600
 * )
 */
class Eupl11andEupl12AreCompatible extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
      'EUPL-1.2',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
      'EUPL-1.2',
    ],
  ];

}
