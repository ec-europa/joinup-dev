<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T16 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Must=Copyleft
 * - <Licence-B>: Must=Lesser copyleft
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "copyleft_can_be_distributed_as_lesser_copyleft",
 *   document_id = "T16",
 *   weight = 1600
 * )
 */
class CopyLeftCanBeDistributedAsLesserCopyLeft extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Must' => [
      'Lesser copyleft',
    ],
  ];

}
