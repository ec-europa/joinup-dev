<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T18 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Compatible=For data
 * - <Licence-B>: Compatible=For software
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "for_data_vs_for_software",
 *   document_id = "T18",
 *   weight = 1800
 * )
 */
class ForDataVsForSoftware extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Compatible' => [
      'For data',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Compatible' => [
      'For software',
    ],
  ];

}
