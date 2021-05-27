<?php

declare(strict_types = 1);

namespace Drupal\entityqueue_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A block that shows the contents of an entityqueue.
 *
 * @Block(
 *   id = "entityqueue_block",
 *   admin_label = @Translation("Entityqueue block"),
 *   category = @Translation("Entityqueue")
 * )
 */
class EntityqueueBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new EntityqueueBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = $this->entityTypeManager
      ->getStorage('entity_subqueue')
      ->load($this->configuration['entityqueue']);

    /** @var \Drupal\entityqueue\EntitySubqueueItemsFieldItemList $item_list */
    $item_list = $subqueue->get('items');

    $build['entities'] = $this->entityTypeManager
      ->getViewBuilder($subqueue->getQueue()->getTargetEntityTypeId())
      ->viewMultiple($item_list->referencedEntities(), $this->configuration['view_mode']);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'entityqueue' => NULL,
      'view_mode' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['entityqueue'] = [
      '#type' => 'select',
      '#title' => $this->t('Entityqueue'),
      '#default_value' => $this->configuration['entityqueue'],
      '#required' => TRUE,
    ];

    /** @var \Drupal\entityqueue\EntityQueueInterface[] $queues */
    $queues = $this->entityTypeManager->getStorage('entity_queue')->loadMultiple();
    $target_types = [];
    foreach ($queues as $queue) {
      $form['entityqueue']['#options'][$queue->id()] = $queue->label();
      $target_types[$queue->getTargetEntityTypeId()] = $queue->getTargetEntityTypeId();
    }

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#default_value' => $this->configuration['view_mode'],
      '#required' => TRUE,
    ];

    /** @var \Drupal\entityqueue\EntityQueueInterface[] $queues */
    $viewmodes = array_intersect_key($this->entityDisplayRepository->getAllViewModes(), $target_types);
    foreach ($viewmodes as $entity_viewmodes) {
      foreach ($entity_viewmodes as $machine_name => $viewmode_info) {
        $form['view_mode']['#options'][$machine_name] = $viewmode_info['label'];
      }
    }

    // Sort the dropdown options alphabetically to make them easier to navigate.
    asort($form['entityqueue']['#options']);
    asort($form['view_mode']['#options']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['entityqueue'] = (string) $form_state->getValue('entityqueue');
    $this->configuration['view_mode'] = (string) $form_state->getValue('view_mode');
  }

}
