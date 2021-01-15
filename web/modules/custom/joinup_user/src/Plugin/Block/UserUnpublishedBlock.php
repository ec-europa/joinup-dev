<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\state_machine_revisions\RevisionManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the unpublished community content owned by the user.
 *
 * @Block(
 *   id = "user_unpublished",
 *   admin_label = @Translation("Unpublished content of user"),
 *   context = {
 *     "user_route" = @ContextDefinition("entity:user", label = @Translation("User from URL"))
 *   }
 * )
 */
class UserUnpublishedBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The revision manager service.
   *
   * @var \Drupal\state_machine_revisions\RevisionManagerInterface
   */
  protected $revisionManager;

  /**
   * Constructs a new UserUnpublishedBlock object.
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
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revision_manager
   *   The revision manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $current_user, CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager, RevisionManagerInterface $revision_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->revisionManager = $revision_manager;
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
      $container->get('entity_type.manager'),
      $container->get('state_machine_revisions.revision_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $rows = $this->getRows();
    if (empty($rows)) {
      return [];
    }
    $build = [
      // The 'listing' child key is needed to avoid copying the #attributes to
      // the parent block.
      // @see \Drupal\block\BlockViewBuilder::preRender()
      '#extra_suggestion' => 'block__separated',
      'listing' => [
        '#type' => 'container',
        '#extra_suggestion' => 'container__grid',
      ],
    ];

    $build['listing'] += $rows;
    return $build;
  }

  /**
   * Receives the unpublished content rows for the current user.
   *
   * @return array
   *   An array of rows to render.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the Search API Index entity storage definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the Search API Index entity storage definition is not found.
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurs during the execution of the search query.
   */
  protected function getRows(): array {
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('unpublished');
    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $index->query();
    $query->addCondition('entity_author', [$this->currentUser->id()], 'IN');
    $results = $query->execute();
    $entities = $this->getResultEntities($results);
    $rows = [];

    foreach ($entities as $weight => $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $rows[$weight] = [
        '#type' => 'container',
        '#extra_suggestion' => 'container__grid_item',
        '#weight' => $weight,
        'entity' => $view,
      ];
    }
    return $rows;
  }

  /**
   * Returns the entities listed in the given query result set.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   The query results object.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The render array for the search results.
   *
   * @throws \Exception
   *    Thrown if the item loaded is not a node or an rdf entity.
   */
  protected function getResultEntities(ResultSetInterface $result): array {
    $results = [];
    foreach ($result->getResultItems() as $item) {
      try {
        $entity = $item->getOriginalObject()->getValue();
        if ($entity instanceof NodeInterface) {
          $entity = $this->revisionManager->loadLatestRevision($entity);
        }
        elseif ($entity instanceof RdfInterface) {
          $entity_id = $item->getOriginalObject()->getValue()->id();
          $entity = $this->getDraftRdf($entity_id);
        }
        else {
          throw new \Exception('Only nodes and Rdf entities should be loaded.');
        }
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
   * Loads the latest revision on an rdf entity.
   *
   * @param int $entity_id
   *   The content id.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The loaded RDF entity.
   */
  protected function getDraftRdf($entity_id): RdfInterface {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $sparql_storage */
    $sparql_storage = $this->entityTypeManager->getStorage('rdf_entity');
    return $sparql_storage->load($entity_id, ['draft']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // The page should be dependent on the user's groups.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $cache_tags = parent::getCacheTags();
    foreach (['node', 'rdf_entity'] as $type) {
      $entity_type = $this->entityTypeManager->getStorage($type)->getEntityType();
      $cache_tags = Cache::mergeTags($cache_tags, $entity_type->getListCacheTags());
    }
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    // Only allow access if the user is viewing their own profile.
    if ($user = $this->currentRouteMatch->getParameter('user')) {
      if ($user && ($user instanceof UserInterface) && $user->id() === $this->currentUser->id()) {
        return parent::blockAccess($account);
      }
    }
    return AccessResult::forbidden();
  }

}
