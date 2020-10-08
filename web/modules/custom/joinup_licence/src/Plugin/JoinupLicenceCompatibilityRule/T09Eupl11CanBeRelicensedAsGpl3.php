<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T09 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=EUPL-1.1
 * - <Licence-B>: SPDX=GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T09",
 *   weight = 900,
 * )
 */
class T09Eupl11CanBeRelicensedAsGpl3 extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];

}
