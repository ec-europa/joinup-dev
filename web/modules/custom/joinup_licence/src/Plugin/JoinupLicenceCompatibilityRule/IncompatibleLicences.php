<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\JoinupLicenceCompatibilityRule;

use Drupal\joinup_licence\Entity\LicenceInterface;
use Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginBase;

/**
 * Rule that links to a document explaining the licences are incompatible.
 *
 * @JoinupLicenceCompatibilityRule(
 *   id = "incompatible_licences",
 *   document_id = "T99",
 *   weight = 9999
 * )
 */
class IncompatibleLicences extends JoinupLicenceCompatibilityRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): bool {
    return FALSE;
  }

}
