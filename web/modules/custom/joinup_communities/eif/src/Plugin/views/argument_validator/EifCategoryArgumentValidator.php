<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\views\argument_validator;

use Drupal\eif\EifInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Defines a argument validator plugin for the EIF category.
 *
 * @ViewsArgumentValidator(
 *   id = "eif_category",
 *   title = @Translation("EIF Category"),
 * )
 */
class EifCategoryArgumentValidator extends ArgumentValidatorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validateArgument($arg): bool {
    return isset(EifInterface::EIF_CATEGORIES[$arg]);
  }

}
