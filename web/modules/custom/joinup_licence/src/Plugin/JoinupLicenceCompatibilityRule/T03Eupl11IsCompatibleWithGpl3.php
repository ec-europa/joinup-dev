<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T03 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only
 * - <Licence-B>: SPDX=EUPL-1.1
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T03",
 *   weight = 300,
 * )
 */
class T03Eupl11IsCompatibleWithGpl3 extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];
  const OUTBOUND_CRITERIA = ['SPDX' => ['EUPL-1.1']];

}
