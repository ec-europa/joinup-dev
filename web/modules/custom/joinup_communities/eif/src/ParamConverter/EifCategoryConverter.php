<?php

declare(strict_types = 1);

namespace Drupal\eif\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\eif\EifInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides am {eif_category} route param converter.
 */
class EifCategoryConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route): bool {
    return isset($definition['type']) && $definition['type'] === 'eif_category';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return isset(EifInterface::EIF_CATEGORIES[$value]) ? $value : NULL;
  }

}
