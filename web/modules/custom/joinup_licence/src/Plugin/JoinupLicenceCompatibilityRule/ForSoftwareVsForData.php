<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T17 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Compatible=For software
 * - <Licence-B>: Compatible=For data
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "for_software_vs_for_data",
 *   document_id = "T17",
 *   weight = 1700
 * )
 */
class ForSoftwareVsForData extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Compatible' => [
      'For software',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Compatible' => [
      'For data',
    ],
  ];

}
