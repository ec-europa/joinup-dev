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
 *   id = "T18",
 *   weight = 1800,
 * )
 */
class T18ForDataVsForSoftware extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'Compatible' => [
      'For data',
    ],
  ];
  const OUTBOUND_CRITERIA = [
    'Compatible' => [
      'For software',
    ],
  ];

}
