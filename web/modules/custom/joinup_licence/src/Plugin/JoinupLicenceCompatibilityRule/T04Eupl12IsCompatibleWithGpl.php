<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T04 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=GPL-2.0-only OR GPL-2.0+ OR GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only
 * - <Licence-B>: SPDX=EUPL-1.2
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T04",
 *   weight = 400,
 * )
 */
class T04Eupl12IsCompatibleWithGpl extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-2.0+',
      'GPL-2.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];
  const OUTBOUND_CRITERIA = ['SPDX' => ['EUPL-1.2']];

}
