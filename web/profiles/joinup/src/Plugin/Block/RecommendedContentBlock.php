<?php

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\og\MembershipManager;

/**
 * Provides a block with the recommended community content for the current user.
 *
 * @Block(
 *  id = "recommended_content",
 *  admin_label = @Translation("Recommended content"),
 * )
 */
class RecommendedContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['count' => 9] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $count = $this->configuration['count'];

    // @todo Show featured content that is pinned by moderators.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3147
    // @todo Provide tailored content for authenticated users that are not a
    //   member of any group, according to their past browsing behaviour.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3427
    $entities = [];

    // If the user is a member of one or more collections or solutions, show
    // the latest content from those.
    $groups = $this->ogMembershipManager->getUserGroups($this->currentUser->getAccount());
    if (!empty($groups['rdf_entity'])) {
      $entities = $this->getContentFromMemberships($groups, $count - count($entities));
    }

    // Show popular content to anonymous users and users without memberships.
    else {
      $entities += $this->getPopularContent($count - count($entities));
    }

    $build = [
      '#attributes' => ['class' => ['listing', 'listing--grid', 'mdl-grid']],
    ];

    foreach ($entities as $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $build[] = [
        '#theme' => 'search_api_field_result',
        '#item' => $view,
        '#entity' => $entity,
      ];
    }

    return $build;
  }

  /**
   * Receives the content of the groups the user is a member of.
   *
   * @param array $groups
   *   The user's memberships.
   * @param int $limit
   *   The number of results to fetch.
   *
   * @return array
   *   An array of rows to render.
   */
  protected function getContentFromMemberships(array $groups, $limit) {
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

    $index = Index::load('published');
    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $index->query();
    $query->addCondition('entity_bundle', CommunityContentHelper::getBundles(), 'IN');
    $query->addCondition('entity_groups', $cids, 'IN');
    $query->sort('created', 'DESC');
    $query->range(0, $limit);
    $results = $query->execute();

    return $this->getResultEntities($results);
  }

  /**
   * Returns the most popular community content according to the visit count.
   *
   * @param int $limit
   *   The number of results to fetch.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The most popular community content entities.
   */
  protected function getPopularContent($limit) {
    $index = Index::load('published');
    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $index->query();
    $query->addCondition('entity_bundle', CommunityContentHelper::getBundles(), 'IN');
    $query->sort('field_visit_count', 'DESC');
    $query->range(0, $limit);
    $results = $query->execute();

    return $this->getResultEntities($results);
  }

  /**
   * Filters the search results, throwing away stale and inaccessible results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $result
   *   The query results object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The valid results, as loaded entities.
   */
  protected function getResultEntities(ResultSetInterface $result) {
    $results = [];
    /* @var $item \Drupal\search_api\Item\ItemInterface */
    foreach ($result->getResultItems() as $item) {
      try {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $item->getOriginalObject()->getValue();
      }
      catch (SearchApiException $e) {
        $entity = NULL;
      }
      // Search results might be stale, so we check if the entity has been
      // found in the system, and if the user has access to view them.
      if (empty($entity) || !$entity->access('view')) {
        continue;
      }
      $results[] = $entity;
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to show'),
      '#default_value' => $this->configuration['count'],
      '#weight' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The block is dependent on the user's groups.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_role']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // The block should be invalidated whenever any node changes.
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
