<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\joinup_licence\Entity\LicenceInterface;

/**
 * Interface for licence compatibility rule plugins.
 */
interface JoinupLicenceCompatibilityRuleInterface {

  /**
   * Checks whether the two licences are verifying this rule.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $inbound_licence
   *   The licence of an existing project of which the code or data is used.
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $outbound_licence
   *   The licence under which the modified or extended code or data is going to
   *   be redistributed.
   *
   * @return bool
   *   TRUE if the two licences are compatible according to this rule.
   */
  public function isVerified(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): bool;

}
