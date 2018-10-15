<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base implementation of a filter plugin.
 */
abstract class FilterPluginBase extends PluginBase implements FilterPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Validation is not supported at the moment.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

}
