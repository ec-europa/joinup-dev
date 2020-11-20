<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\Entity\LicenceInterface;
use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Rule that links to a document explaining the licences are incompatible.
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "INCOMPATIBLE",
 * )
 */
class IncompatibleLicences extends JoinupLicenceCompatibilityRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function isVerified(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): bool {
    return TRUE;
  }

}
