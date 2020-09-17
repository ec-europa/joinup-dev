<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T07 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=GPL-2.0-only OR GPL-2.0+ OR GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only
 * - <Licence-B>: SPDX=GPL-2.0-only OR SPDX=GPL-3.0-only
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "gpl_compatible_licences_are_compatible_with_gpl",
 *   document_id = "T07",
 *   weight = 700
 * )
 */
class GplCompatibleLicencesAreCompatibleWithGpl extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-2.0+',
      'GPL-2.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Compatible' => [
      'GPL',
    ],
  ];

}
