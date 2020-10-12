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
 *   id = "T17",
 *   weight = 1700,
 * )
 */
class T17ForSoftwareVsForData extends JoinupLicenceCompatibilityRulePluginBase {

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
