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
 *   id = "eupl_1_1_can_be_relicensed_as_gpl_2_and_others",
 *   document_id = "T08",
 *   weight = 800
 * )
 */
class Eupl11CanBeRelicensedAsGpl2AndOthers extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
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
