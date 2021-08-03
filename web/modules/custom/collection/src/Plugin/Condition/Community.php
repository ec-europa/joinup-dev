<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Community' condition.
 *
 * @Condition(
 *   id = "collection",
 *   label = @Translation("Communities"),
 *   context_definitions = {
 *     "og" = @ContextDefinition("entity:rdf_entity:collection", label = @Translation("Community"))
 *   }
 * )
 */
class Community extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['collections'] = [
      '#title' => $this->t('Show on Community pages'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['collections'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Certain form elements may return 'communities' as 0/1. Cast here to
    // ensure the data is in the expected type.
    $this->configuration['collections'] = (bool) $form_state->getValue('collections');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Community pages');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if ($this->configuration['collections'] == FALSE && !$this->isNegated()) {
      return TRUE;
    }
    return !empty($this->configuration['collections']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['collections' => FALSE] + parent::defaultConfiguration();
  }

}
