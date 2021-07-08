<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an explore item block.
 *
 * @Block(
 *   id = "joinup_front_page_explore_item",
 *   admin_label = @Translation("Explore item")
 * )
 */
class ExploreItemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Node view mode.
   *
   * @var string
   */
  protected $viewMode = 'explore_item';

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ExploreItemBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->messenger = $messenger;
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
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $data = [
      'solutions' => [
        'name' => $this->t('Solutions'),
        'data' => $this->getRdfEntity('solution'),
        'url' => '/search?keys=&f[0]=type%3Asolution',
      ],
      'collections' => [
        'name' => $this->t('Collections'),
        'data' => $this->getRdfEntity('collection'),
        'url' => '/search?keys=&f[0]=type%3Acollection',
      ],
      'news' => [
        'name' => $this->t('News'),
        'data' => $this->getContent('news'),
        'url' => '/search?keys=&f[0]=type%3Anews',
      ],
      'events' => [
        'name' => $this->t('Events'),
        'data' => $this->getContent('events'),
        'url' => '/search?keys=&f[0]=type%3Aevent',
      ],
    ];

    return [
      '#theme' => 'explore_block',
      '#data' => $data,
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [
      'node:news',
      'node:events',
      'rdf_entity:solution',
      'rdf_entity:collection',
    ];

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * Get entity data.
   *
   * @param string $type
   *   The type of content.
   *
   * @return array
   *   List of content with view mode "explore_item".
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getContent($type): array {
    $content = [];
    $entity = $this->entityManager->getStorage('node');
    $view_builder = $this->entityManager->getViewBuilder('node');
    $query = $entity->getQuery();

    $ids = $query->condition('status', 1)
      ->condition('type', $type)
      ->sort('created')
      ->pager(12)
      ->execute();

    $entities = $entity->loadMultiple($ids);
    foreach ($entities as $node) {
      $content[] = $view_builder->view($node, $this->viewMode);
    }

    return $content;
  }

  /**
   * Get rdf entity data.
   *
   * @param string $type
   *   The type of content.
   *
   * @return array
   *   List of rdf entity with view mode "explore_item".
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getRdfEntity($type): array {
    $rdf = [];
    $entity = $this->entityManager->getStorage('rdf_entity');
    $view_builder = $this->entityManager->getViewBuilder('rdf_entity');
    $query = $entity->getQuery();

    $ids = $query->condition('rid', $type)
      ->sort('created')
      ->pager(12)
      ->execute();

    $entities = $entity->loadMultiple($ids);

    foreach ($entities as $node) {
      $rdf[] = $view_builder->view($node, $this->viewMode);
    }

    return $rdf;
  }

}
