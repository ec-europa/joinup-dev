<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\Component\Plugin\PluginBase;
use Drupal\joinup_licence\Entity\LicenceInterface;

/**
 * Base class for licence compatibility rule plugins.
 */
abstract class JoinupLicenceCompatibilityRulePluginBase extends PluginBase implements JoinupLicenceCompatibilityRuleInterface {

  /**
   * The criteria to which the use licence should adhere.
   */
  const INBOUND_CRITERIA = [];

  /**
   * The criteria to which the use redistribute as licence should adhere.
   */
  const OUTBOUND_CRITERIA = [];

  /**
   * {@inheritdoc}
   */
  public function isVerified(LicenceInterface $inbound_licence, LicenceInterface $outbound_licence): bool {
    /** @var \Drupal\joinup_licence\Entity\LicenceInterface $licence */
    foreach ([
      [$inbound_licence, static::INBOUND_CRITERIA],
      [$outbound_licence, static::OUTBOUND_CRITERIA],
    ] as [$licence, $criteria]) {
      foreach ($criteria as $type => $matches) {
        switch ($type) {
          case 'SPDX':
            if (!in_array($licence->getSpdxLicenceId(), $matches)) {
              return FALSE;
            }
            break;

          default:
            foreach ($matches as $legal_type) {
              if ($licence->hasLegalType($type, $legal_type)) {
                continue 3;
              }
            }
            return FALSE;
        }
      }
    }
    return TRUE;
  }

}
