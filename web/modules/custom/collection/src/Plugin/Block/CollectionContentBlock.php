<?php

namespace Drupal\collection\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that shows all content within the collection.
 *
 * @Block(
 *  id = "collection_content_block",
 *  admin_label = @Translation("Collection content"),
 * )
 */
class CollectionContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The collection.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $collection;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * The entity type manager, needed to load entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity.manager'),
      $container->get('collection.collection_route_context'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Constructs a CollectionContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $collection_context
   *   The collection context.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, ContextProviderInterface $collection_context, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
    $this->collection = $collection_context->getRuntimeContexts(['og'])['og']->getContextValue();
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (empty($this->collection)) {
      throw new \Exception('The "Collection content" block can only be shown on collection pages.');
    }
    $content_ids = $this->membershipManager->getGroupContentIds($this->collection);
    $list = array();
    foreach ($content_ids as $entity_type => $ids) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entities = $storage->loadMultiple($ids);
      $children = [];
      foreach ($entities as $entity) {
        $children[] = array('#markup' => $entity->link());
      }
      if ($children) {
        $list[] = array(
          '#markup' => $storage->getEntityType()->getLabel(),
          'children' => $children,
        );
      }
    }
    $build = array(
      'list' => [
        '#theme' => 'item_list',
        '#items' => $list,
        '#cache' => [
          'tags' => ['og_group_content:' . $this->collection->id()],
        ],
      ],
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable caching.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return ($this->currentRouteMatch->getRouteName() == 'entity.rdf_entity.canonical') ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
