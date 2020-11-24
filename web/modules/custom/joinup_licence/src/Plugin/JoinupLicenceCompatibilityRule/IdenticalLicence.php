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
 *   id = "identical_licence",
 *   document_id = "T01",
 *   weight = 100
 * )
 */
class IdenticalLicence extends JoinupLicenceCompatibilityRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): bool {
    return $use_licence->getSpdxLicenceId() === $redistribute_as_licence->getSpdxLicenceId();
  }

}
