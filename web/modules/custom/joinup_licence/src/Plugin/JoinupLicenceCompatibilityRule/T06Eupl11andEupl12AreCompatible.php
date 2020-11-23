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
 *   id = "T06",
 *   weight = 600,
 * )
 */
class T06Eupl11andEupl12AreCompatible extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
      'EUPL-1.2',
    ],
  ];
  const OUTBOUND_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
      'EUPL-1.2',
    ],
  ];

}
