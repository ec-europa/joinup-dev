<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T08 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=EUPL-1.1
 * - <Licence-B>: SPDX=GPL-2.0-only OR OSL-3.0 OR CECILL-2.0 OR CECILL-2.1 OR EPL-2.0 OR EPL-2.1 or CPL-1.0
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T08",
 *   weight = 800,
 * )
 */
class T08Eupl11CanBeRelicensedAsGpl2AndOthers extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
    ],
  ];
  const OUTBOUND_CRITERIA = [
    'SPDX' => [
      'CECILL-2.0',
      'CECILL-2.1',
      'CPL-1.0',
      'EPL-2.0',
      'EPL-2.1',
      'GPL-2.0-only',
      'OSL-3.0',
    ],
  ];

}
