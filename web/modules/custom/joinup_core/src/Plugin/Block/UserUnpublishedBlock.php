<?php

namespace Drupal\joinup_core\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a block with the recommended community content for the current user.
 *
 * @Block(
 *  id = "user_unpublished",
 *  admin_label = @Translation("Unpublished content of user"),
 * )
 */
class UserUnpublishedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The community content bundle ids.
   *
   * @var array
   */
  const COMMUNITY_BUNDLES = [
    'discussion',
    'document',
    'event',
    'news',
  ];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RecommendedContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user proxy.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $current_user, CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
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
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $rows = $this->getRows();

    $build['#attributes'] = [
      'class' => ['listing', 'listing--grid', 'mdl-grid'],
    ];

    $build += $rows;
    return $build;
  }

  /**
   * Receives the unpublished content rows for the current user.
   *
   * @return array
   *   An array of rows to render.
   */
  protected function getRows() {
    $index = Index::load('published');
    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $index->query();
    $query->addCondition('entity_bundle', self::COMMUNITY_BUNDLES, 'IN');
    $query->addCondition('authored_by', $this->currentUser->id(), 'IN');
    $query->sort('created', 'DESC');
    $query->range(0, 9);
    $results = $query->execute();
    $entities = $this->getResultEntities($results);
    $rows = [];

    foreach ($entities as $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $rows[] = [
        '#theme' => 'search_api_field_result',
        '#item' => $view,
        '#entity' => $entity,
      ];
    }
    return $rows;
  }

  /**
   * Builds a renderable array for the search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   The query results object.
   *
   * @return array
   *   The render array for the search results.
   */
  protected function getResultEntities(ResultSetInterface $result) {
    $results = [];
    /* @var $item \Drupal\search_api\Item\ItemInterface */
    foreach ($result->getResultItems() as $item) {
      try {
        $entity_id = $item->getOriginalObject()->getValue()->id();
        $entity = $this->getLatestRevision($entity_id);
      }
      catch (SearchApiException $e) {
        $entity = NULL;
      }
      // Search results might be stale, so we check if the entity has been
      // found in the system.
      if (!$entity) {
        continue;
      }
      if (!$entity->access('view')) {
        continue;
      }
      $results[] = $entity;
    }
    return $results;
  }

  /**
   * Loads the latest revision on an entity.
   *
   * @param int $entity_id
   *   The content id.
   *
   * @return \Drupal\node\NodeInterface
   *   The loaded node.
   */
  protected function getLatestRevision($entity_id) {
    $storage = $this->entityTypeManager->getStorage('node');
    $revision_ids = $storage->getQuery()
      ->allRevisions()
      ->condition('nid', $entity_id)
      ->sort('revision_id', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($revision_ids)) {
      return NULL;
    }

    $revision_id = array_keys($revision_ids)[0];
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $storage->loadRevision($revision_id);
    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * The page should be dependent on the user's groups.
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  /**
   * {@inheritdoc}
   *
   * Only allow access if the user is viewing his own profile.
   */
  protected function blockAccess(AccountInterface $account) {
    if ($user = $this->currentRouteMatch->getParameter('user')) {
      if ($user && ($user instanceof UserInterface) && $user->id() === $this->currentUser->id()) {
        return parent::blockAccess($account);
      }
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
