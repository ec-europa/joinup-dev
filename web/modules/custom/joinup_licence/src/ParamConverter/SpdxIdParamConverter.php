<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\joinup_licence\Entity\Licence;
use Drupal\joinup_licence\Entity\LicenceInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts an SPDX ID into its corresponding Licence entity.
 */
class SpdxIdParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route): bool {
    return isset($definition['type']) && $definition['type'] === 'licence_spdx_id';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults): ?LicenceInterface {
    // Some licences contain a plus character (e.g. "GPL-2.0+") and this gets
    // converted into a space by the URL decoder. Revert this change.
    $spdx_id = str_replace(' ', '+', $value);

    return Licence::loadBySpdxId($spdx_id);
  }

}
