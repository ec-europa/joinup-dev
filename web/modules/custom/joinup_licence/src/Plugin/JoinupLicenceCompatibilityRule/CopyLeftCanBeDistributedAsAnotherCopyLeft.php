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
 *   id = "copyleft_can_be_distributed_as_another_copyleft",
 *   document_id = "T15",
 *   weight = 1500
 * )
 */
class CopyLeftCanBeDistributedAsAnotherCopyLeft extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
      'Lesser copyleft',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
    ],
  ];

}
