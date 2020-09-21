<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T14 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Must=Copyleft OR Must=Lesser copyleft
 * - <Licence-B>: Compatible=Permissive
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "copyleft_can_be_distributed_as_permissive_if_unchanged",
 *   document_id = "T14",
 *   weight = 1400
 * )
 */
class CopyLeftCanBeDistributedAsPermissiveIfUnchanged extends JoinupLicenceCompatibilityRulePluginBase {

  const USE_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
      'Lesser copyleft',
    ],
  ];
  const REDISTRIBUTE_AS_CRITERIA = [
    'Compatible' => [
      'Permissive',
    ],
  ];

}
