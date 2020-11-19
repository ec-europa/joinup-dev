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
 *   id = "T14",
 *   weight = 1400,
 * )
 */
class T14CopyLeftCanBeDistributedAsPermissiveIfUnchanged extends JoinupLicenceCompatibilityRulePluginBase {

  const INBOUND_CRITERIA = [
    'Must' => [
      'Copyleft/Share a.',
      'Lesser copyleft',
    ],
  ];
  const OUTBOUND_CRITERIA = [
    'Compatible' => [
      'Permissive',
    ],
  ];

}
