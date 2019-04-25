<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\SearchApiConfigEntityStorage;
use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field that shows the user authorship.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("joinup_authorship")
 */
class Authorship extends Boolean {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new plugin instance.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
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
  public function usesGroupBy(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Do nothing, to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['publication_states'] = ['default' => ['published']];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $indexes = [];
    foreach ($this->getSearchApiIndexStorage()->loadMultiple() as $index) {
      $indexes[$index->id()] = $index->label();
    }

    $form['publication_states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Publication states'),
      '#options' => $indexes,
      '#default_value' => $this->options['publication_states'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);
    $publication_states = $form_state->getValue(['options', 'publication_states']);
    // Normalize the array from: ['published' => TRUE, 'unpublished' => FALSE]
    // to ['published'].
    $publication_states = array_keys(array_filter($publication_states));
    $form_state->setValue(['options', 'publication_states'], $publication_states);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $count = 0;
    /** @var \Drupal\search_api\IndexInterface $index */
    foreach ($this->getSearchApiIndexStorage()->loadMultiple($this->options['publication_states']) as $index) {
      if (isset($values->uid)) {
        $count += $index->query()
          ->addCondition('entity_author', $values->uid)
          ->execute()
          ->getResultCount();
      }
    }
    $values->{$this->field_alias} = (bool) $count;

    return parent::render($values);
  }

  /**
   * Returns the Search API index storage handler.
   *
   * @return \Drupal\search_api\Entity\SearchApiConfigEntityStorage
   *   The storage handler.
   */
  protected function getSearchApiIndexStorage(): SearchApiConfigEntityStorage {
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage('search_api_index');
    return $storage;
  }

}
