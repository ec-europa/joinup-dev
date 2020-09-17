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
 *   id = "eupl_1_2_is_compatible_with_gpl",
 *   document_id = "T04",
 *   weight = 400
 * )
 */
class Eupl12IsCompatibleWithGpl extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-2.0+',
      'GPL-2.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = ['SPDX' => ['EUPL-1.2']];

}
