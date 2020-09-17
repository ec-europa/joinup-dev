<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T05 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=GPL-2.0-only OR SPDX=GPL-3.0-only
 * - <Licence-B>: SPDX=GPL-2.0-only OR SPDX=GPL-3.0-only
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "gpl_2_and_gpl_3_are_compatible",
 *   document_id = "T05",
 *   weight = 500
 * )
 */
class Gpl2andGpl3AreCompatible extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'GPL-2.0-only',
      'GPL-3.0-only',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'GPL-2.0-only',
      'GPL-3.0-only',
    ],
  ];

}
