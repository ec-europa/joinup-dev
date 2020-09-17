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
 *   id = "eupl_1_1_is_compatible_with_gpl_3",
 *   document_id = "T03",
 *   weight = 300
 * )
 */
class Eupl11IsCompatibleWithGpl3 extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = ['SPDX' => ['EUPL-1.1']];

}
