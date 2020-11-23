<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\Entity\LicenceInterface;
use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Implementation of the T01 rule.
 *
 * @codingStandardsIgnoreStart
 * - <Licence-A>: SPDX=any licence
 * - <Licence-B>: SPDX=(same as <Licence-A>)
 * @codingStandardsIgnoreEnd
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "T01",
 *   weight = 100,
 * )
 */
class T01IdenticalLicence extends JoinupLicenceCompatibilityRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function isVerified(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): bool {
    return $inbound_licence->getSpdxLicenceId() === $outbound_licence->getSpdxLicenceId();
  }

}
