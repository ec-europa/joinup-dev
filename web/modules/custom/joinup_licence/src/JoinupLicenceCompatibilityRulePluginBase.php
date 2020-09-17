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
  const USE_CRITERIA = [];

  /**
   * The criteria to which the use redistribute as licence should adhere.
   */
  const REDISTRIBUTE_AS_CRITERIA = [];

  /**
   * {@inheritdoc}
   */
  public function getDocumentId(): string {
    return (string) $this->pluginDefinition['document_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCompatible(LicenceInterface $use_licence, LicenceInterface $redistribute_as_licence): bool {
    /** @var \Drupal\joinup_licence\Entity\LicenceInterface $licence */
    foreach ([
      [$use_licence, static::USE_CRITERIA],
      [$redistribute_as_licence, static::REDISTRIBUTE_AS_CRITERIA],
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
