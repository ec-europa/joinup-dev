<?php

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\og\MembershipManager;

/**
 * Provides a 'RecommendedContentBlock' block.
 *
 * @Block(
 *  id = "recommended_content",
 *  admin_label = @Translation("Recommended content"),
 * )
 */
class RecommendedContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * An array of bundles.
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
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\og\MembershipManager definition.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
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
   * @param \Drupal\og\MembershipManager $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $current_user, MembershipManager $og_membership_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->ogMembershipManager = $og_membership_manager;
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
      $container->get('og.membership_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides empty homepage.
   *
   * @return array
   *   A render array for the homepage.
   */
  public function build() {
    $groups = $this->ogMembershipManager->getUserGroups($this->currentUser->getAccount());
    $rows = [];
    if (!empty($groups['rdf_entity'])) {
      $rows = $this->getContentFromMemberships($groups);
    }
    // @todo: Else: Provide content with site-wide content.
    // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3427

    $build['#attributes'] = [
      'class' => ['listing', 'listing--grid', 'mdl-grid'],
    ];

    $build += $rows;
    return $build;
  }

  /**
   * Receives the content of the groups the user is a member of.
   *
   * @param array $groups
   *   The user's memberships.
   *
   * @return array
   *   An array of rows to render.
   */
  protected function getContentFromMemberships(array $groups) {
    $rdf_entities = isset($groups['rdf_entity']) ? $groups['rdf_entity'] : [];

    // Only show content from the first 100 groups to avoid hitting the query
    // size limit.
    if (count($rdf_entities) > 100) {
      $subset = array_chunk($rdf_entities, 100);
      $rdf_entities = reset($subset);
    }

    $cids = array_map(function ($rdf_entity) {
      return $rdf_entity->id();
    }, $rdf_entities);

    $index = Index::load('collections');
    $query = $index->query();
    $query->addCondition('entity_bundle', self::COMMUNITY_BUNDLES, 'IN');
    $query->addCondition('entity_groups', $cids, 'IN');
    $query->sort('created', 'DESC');
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
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $item->getOriginalObject()->getValue();
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

}
