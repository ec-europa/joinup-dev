<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\SearchApiField\Filter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Query\ConditionSetInterface;
use Drupal\search_api_field\Plugin\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins that show an entity autocomplete filter.
 */
abstract class EntityAutocompleteBase extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['target_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->getEntityType(),
      '#required' => TRUE,
      '#maxlength' => 1024,
      '#target_type' => $this->getEntityType(),
      '#default_value' => $this->referencedEntity(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['target_id'] = $form_state->getValue('target_id');
  }

  /**
   * Returns the entity referenced by this widget, if any.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity referenced in the configuration, NULL otherwise.
   */
  protected function referencedEntity(): ?EntityInterface {
    if (empty($this->configuration['target_id'])) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage($this->getEntityType())->load($this->configuration['target_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function applyFilter(ConditionSetInterface $condition): void {
    $condition->addCondition($this->configuration['field'], $this->configuration['target_id']);
  }

  /**
   * Returns the entity type supported by the plugin.
   *
   * @return string
   *   The entity type machine name.
   */
  abstract protected function getEntityType(): string;

}
