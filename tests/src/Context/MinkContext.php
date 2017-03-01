<?php

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;
use Drupal\joinup\Traits\MaterialDesignTrait;

/**
 * Provides step definitions for interacting with Mink.
 */
class MinkContext extends DrupalExtensionMinkContext {

  use MaterialDesignTrait;

  /**
   * {@inheritdoc}
   */
  public function checkOption($option) {
    // Overrides the default method for checking checkboxes to make it
    // compatible with material design.
    $option = $this->fixStepArgument($option);
    $this->checkMaterialDesignField($option, $this->getSession()->getPage());
  }

  /**
   * {@inheritdoc}
   */
  public function uncheckOption($option) {
    // Overrides the default method for unchecking checkboxes to make it
    // compatible with material design.
    $option = $this->fixStepArgument($option);
    $this->uncheckMaterialDesignField($option, $this->getSession()->getPage());
  }

}
