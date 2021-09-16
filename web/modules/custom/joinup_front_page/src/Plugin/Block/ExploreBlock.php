<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that allows the user to explore recent content.
 *
 * This block is placed on the front page and shows a swipeable list of recently
 * published content of 4 types: collections, solutions, news and events. The
 * intention is that users can get a quick view of what is new on Joinup.
 *
 * For each content type the 12 most recent items are shown. They are rendered
 * using the `explore_item` view mode which is exclusive for this block.
 *
 * The user can switch between the 4 types by clicking on tabs.
 *
 * @Block(
 *   id = "joinup_front_page_explore_block",
 *   admin_label = @Translation("Explore block")
 * )
 */
class ExploreBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  protected $entityTypeManager;

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
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
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
        'label' => $this->t('Solutions'),
        'plural_type' => $this->t('solutions'),
        'data' => $this->getRdfEntities('solution'),
        'url' => '/search?keys=&f[0]=type%3Asolution',
      ],
      'collections' => [
        'label' => $this->t('Collections'),
        'plural_type' => $this->t('collections'),
        'data' => $this->getRdfEntities('collection'),
        'url' => '/search?keys=&f[0]=type%3Acollection',
      ],
      'news' => [
        'label' => $this->t('News'),
        'plural_type' => $this->t('news'),
        'data' => $this->getContent('news'),
        'url' => '/search?keys=&f[0]=type%3Anews',
      ],
      'events' => [
        'label' => $this->t('Events'),
        'plural_type' => $this->t('events'),
        'data' => $this->getContent('event'),
        'url' => '/search?keys=&f[0]=type%3Aevent',
      ],
    ];

    return [
      '#theme' => 'explore_block',
      '#data' => $data,
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This block shows fixed content which doesn't vary by any context.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Refresh the content at least once every 6 hours.
    return 60 * 60 * 6;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Since this block is shown on the front page it should not be invalidated
    // whenever any of the content types shown in it change. The front page is
    // heavily trafficked and too frequent cache invalidations might affect
    // performance.
    return [];
  }

  /**
   * Returns the 12 most recent nodes of the given type as render arrays.
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
  protected function getContent(string $type): array {
    $content = [];
    $entity = $this->entityTypeManager->getStorage('node');
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $query = $entity->getQuery();

    $ids = $query->condition('status', 1)
      ->condition('type', $type)
      ->sort('created', 'DESC')
      ->range(0, 12)
      ->execute();

    $entities = $entity->loadMultiple($ids);
    foreach ($entities as $node) {
      $content[] = $view_builder->view($node, $this->viewMode);
    }

    return $content;
  }

  /**
   * Returns the 12 most recent RDF entities of the given type as render arrays.
   *
   * @param string $type
   *   The RDF entity type to return.
   *
   * @return array
   *   List of rdf entity with view mode "explore_item".
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRdfEntities(string $type): array {
    $rdf = [];
    $entity = $this->entityTypeManager->getStorage('rdf_entity');
    $view_builder = $this->entityTypeManager->getViewBuilder('rdf_entity');
    $query = $entity->getQuery();

    $ids = $query->condition('rid', $type)
      ->graphs(['default'])
      ->sort('created', 'DESC')
      ->range(0, 12)
      ->execute();

    $entities = $entity->loadMultiple($ids);

    foreach ($entities as $rdf_entity) {
      $rdf[] = $view_builder->view($rdf_entity, $this->viewMode);
    }

    return $rdf;
  }

}
