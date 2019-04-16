<?php

declare(strict_types = 1);

namespace Drupal\joinup\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\og\MembershipManager;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the recommended community content for the current user.
 *
 * This is the block that is responsible for the content and tiles that are
 * shown on the homepage.
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
   * A list of entities to render in the block.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities = [];

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
    $build = [
      'header' => [
        '#type' => 'inline_template',
        '#template' => '<p>{% trans %}Joinup is a collaborative platform created by the European Commission and funded by the European Union via the <a href="https://ec.europa.eu/isa2/">Interoperability solutions for public administrations, businesses and citizens</a> (ISA<sup>2</sup>) Programme. It offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions.{% endtrans %}</p>',
      ],
    ];

    $count = $this->configuration['count'];

    // @todo Provide tailored content for authenticated users that are not a
    //   member of any group, according to their past browsing behaviour.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3427
    $this->entities = $this->getPinnedEntities($count);

    // If the user is a member of one or more collections or solutions, show
    // the latest content from those.
    $group_ids = $this->ogMembershipManager->getUserGroupIds($this->currentUser->getAccount());
    if (!empty($group_ids['rdf_entity'])) {
      $this->entities += $this->getContentFromMemberships($group_ids, $count - count($this->entities));
    }
    // Show popular content to anonymous users and users without memberships.
    else {
      $this->entities += $this->getPopularContent($count - count($this->entities));
    }

    $build['listing'] = [
      '#type' => 'container',
      '#extra_suggestion' => 'container__grid',
    ];

    foreach ($this->entities as $entity) {
      $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'view_mode_tile');
      $build['listing'][] = [
        '#theme' => 'search_api_field_result',
        '#item' => $view,
        '#entity' => $entity,
      ];
    }

    return $build;
  }

  /**
   * Retrieves the entities that are pinned site-wide.
   *
   * @param int $limit
   *   The number of results to fetch.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An array of pinned entities to render.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred during the search.
   */
  protected function getPinnedEntities(int $limit): array {
    // Early exit if we do not need to retrieve any data.
    if ($limit === 0) {
      return [];
    }

    $query = $this->getPublishedIndex()->query();
    $query->addCondition('site_pinned', TRUE);
    $query->sort('entity_created', 'DESC');
    $query->range(0, $limit);
    $results = $query->execute();

    return $this->getResultEntities($results);
  }

  /**
   * Receives the content of the groups the user is a member of.
   *
   * @param array $group_ids
   *   The user's membership IDs.
   * @param int $limit
   *   The number of results to fetch.
   *
   * @return array
   *   An array of rows to render.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred during the search for group content.
   */
  protected function getContentFromMemberships(array $group_ids, int $limit): array {
    // Early exit if we do not need to retrieve any data.
    if ($limit === 0) {
      return [];
    }

    $rdf_entity_ids = $group_ids['rdf_entity'] ?? [];

    // Only show content from the first 100 groups to avoid hitting the query
    // size limit.
    if (count($rdf_entity_ids) > 100) {
      $subset = array_chunk($rdf_entity_ids, 100);
      $rdf_entity_ids = reset($subset);
    }

    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $this->getPublishedIndex()->query();
    $query->addCondition('entity_bundle', CommunityContentHelper::BUNDLES, 'IN');
    $query->addCondition('entity_groups', $rdf_entity_ids, 'IN');
    $query->sort('entity_created', 'DESC');
    $query->range(0, $limit);
    $this->excludeEntitiesFromQuery($query);
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
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred during the search.
   */
  protected function getPopularContent(int $limit): array {
    // Early exit if we do not need to retrieve any data.
    if ($limit === 0) {
      return [];
    }

    /** @var \Drupal\search_api\Query\QueryInterface $query */
    $query = $this->getPublishedIndex()->query();
    $query->addCondition('entity_bundle', CommunityContentHelper::BUNDLES, 'IN');
    $query->sort('field_visit_count', 'DESC');
    $query->sort('entity_created', 'DESC');
    $query->range(0, $limit);
    $this->excludeEntitiesFromQuery($query);
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
  protected function getResultEntities(ResultSetInterface $result): array {
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
      $results[$entity->getEntityTypeId() . '/' . $entity->id()] = $entity;
    }
    return $results;
  }

  /**
   * Retrieves the published Solr index.
   *
   * @return \Drupal\search_api\Entity\Index
   *   The loaded search index.
   */
  protected function getPublishedIndex() {
    // The Joinup profile is depending on the Search API module so we can
    // reasonably assume that the entity storage for it is defined. Since entity
    // storage in Drupal is dynamic, Drupal core performs a number of checks
    // when the storage is accessed and will throw exceptions if it is missing
    // or ill-defined. These checks are not applicable to our situation.
    // Instead of letting these hypothetical exceptions bubble up we convert
    // them to unchecked runtime exceptions. Unchecked exceptions will still be
    // thrown and logged in the extremely rare case that the storage would go
    // missing, but then we do not need to catch or document them in the calling
    // code.
    try {
      /** @var \Drupal\search_api\Entity\Index $index */
      $index = $this->entityTypeManager->getStorage('search_api_index')->load('published');
    }
    catch (PluginNotFoundException $e) {
      throw new \RuntimeException('The Search API Index entity storage is not found.', 0, $e);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('The Search API Index entity storage definition is invalid.', 0, $e);
    }

    return $index;
  }

  /**
   * Excludes the entities already fetched from the query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query being run.
   */
  protected function excludeEntitiesFromQuery(QueryInterface $query): void {
    if (empty($this->entities)) {
      return;
    }

    // The entities can be either rdf entities or nodes. The ID key used in Solr
    // differs based on their type, so we group them and add the conditions
    // separately.
    $exclude = [];
    foreach ($this->entities as $entity) {
      $exclude[$entity->getEntityTypeId()][] = $entity->id();
    }

    if (!empty($exclude['node'])) {
      $query->addCondition('nid', $exclude['node'], 'NOT IN');
    }

    if (!empty($exclude['rdf_entity'])) {
      $query->addCondition('id', $exclude['rdf_entity'], 'NOT IN');
    }
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
    return Cache::mergeTags(parent::getCacheTags(), ['node_list', 'rdf_entity_list']);
  }

}
