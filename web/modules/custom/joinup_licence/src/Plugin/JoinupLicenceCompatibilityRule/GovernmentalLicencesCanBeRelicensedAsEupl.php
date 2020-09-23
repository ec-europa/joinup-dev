<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T11 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=CeCILL-2.1 OR LiLiQ-Rplus-1.1
 * - <Licence-B>: SPDX=EUPL-1.1 OR EUPL-1.2
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "governmental_licences_can_be_relicensed_as_eupl",
 *   document_id = "T11",
 *   weight = 1100
 * )
 */
class GovernmentalLicencesCanBeRelicensedAsEupl extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'SPDX' => [
      'CECILL-2.1',
      'LiLiQ-Rplus-1.1',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'SPDX' => [
      'EUPL-1.1',
      'EUPL-1.2',
    ],
  ];

}
