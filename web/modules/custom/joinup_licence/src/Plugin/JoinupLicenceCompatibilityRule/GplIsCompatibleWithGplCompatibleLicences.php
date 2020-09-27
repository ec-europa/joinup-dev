<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T13 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Compatible=GPL
 * - <Licence-B>: SPDX=GPL-2.0-only OR GPL-2.0+ OR GPL-3.0-only OR GPL-3.0-or-later OR AGPL-3.0-only
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "gpl_is_compatible_with_gpl_compatible_licences",
 *   document_id = "T13",
 *   weight = 1300
 * )
 */
class GplIsCompatibleWithGplCompatibleLicences extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Compatible' => [
      'GPL',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'AGPL-3.0-only',
      'GPL-2.0+',
      'GPL-2.0-only',
      'GPL-3.0-only',
      'GPL-3.0-or-later',
    ],
  ];

}
