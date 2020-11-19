<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\Entity\LicenceInterface;
use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T12 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: Compatible=Permissive
 * - <Licence-B>: SPDX=any licence
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T12",
 *   weight = 1200,
 * )
 */
class T12AnyLicenceCanRedistributePermissiveLicence extends JoinupLicenceCompatibilityRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function isVerified(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): bool {
    return $inbound_licence->hasLegalType('Compatible', 'Permissive');
  }

}
