<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T15 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Must=Copyleft OR Must=Lesser copyleft
 * - <Licence-B>: Must=Copyleft
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T15",
 *   weight = 1500,
 * )
 */
class T15CopyLeftCanBeDistributedAsAnotherCopyLeft extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
      'Lesser copyleft',
    ],
  ];
  const OUTBOUND_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
    ],
  ];

}
