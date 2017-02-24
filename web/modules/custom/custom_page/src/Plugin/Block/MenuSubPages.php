<?php

namespace Drupal\custom_page\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MenuSubPages' block.
 *
 * @Block(
 *  id = "menu_sub_pages",
 *  admin_label = @Translation("Menu sub pages"),
 * )
 */
class MenuSubPages extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $joinupCoreRelationsManager;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The parent entity derived from the collection context.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $collection;

  /**
   * The ogmenu instance id.
   *
   * @var string
   */
  protected $ogMenuInstanceId;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The joinup relation manager service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $collection_context
   *   The collection context.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link tree service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The og membership manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, JoinupRelationManager $relation_manager, ContextProviderInterface $collection_context, MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->joinupCoreRelationsManager = $relation_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->currentRouteMatch = $route_match;
    $this->membershipManager = $membership_manager;

    $collection_contexts = $collection_context->getRuntimeContexts(['og']);
    if ($collection_contexts && $collection_contexts['og']->hasContextValue()) {
      $this->collection = $collection_contexts['og']->getContextValue();
      $results = $this->membershipManager->getGroupContentIds($this->collection, ['ogmenu_instance']);

      if (!empty($results)) {
        $this->ogMenuInstanceId = reset($results['ogmenu_instance']);
      }
    }
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
      $container->get('entity.repository'),
      $container->get('joinup_core.relations_manager'),
      $container->get('collection.collection_route_context'),
      $container->get('plugin.manager.menu.link'),
      $container->get('current_route_match'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];
    $child_links = $this->getChildLinks();
    if (empty($child_links)) {
      return $build;
    }
    foreach ($child_links as $link) {
      $parameters = $link->getUrlObject()->getRouteParameters();
      $node_id = $parameters['node'];
      $custom_page = $this->entityTypeManager->getStorage('node')->load($node_id);
      $build = $this->entityTypeManager->getViewBuilder('node')->view($custom_page, 'view_mode_tile');
      $items[$link->getWeight()] = [
        '#type' => 'container',
        '#weight' => $link->getWeight(),
        '#attributes' => [
          'class' => [
            'listing__item',
            'listing__item--tile',
            'mdl-cell',
            'mdl-cell--4-col',
          ],
        ],
        $custom_page->id() => $build,
      ];
    }

    $build = [
      '#type' => 'item',
      0 => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['listing', 'listing--grid', 'mdl-grid'],
        ],
      ],
    ];
    $build[0] += $items;

    return $build;
  }

  /**
   * Returns all child links of the current page's link in the ogmenu.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent[]
   *    An array of menu links.
   */
  protected function getChildLinks() {
    $links = $this->menuLinkManager->loadLinksByRoute($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), 'ogmenu-' . $this->ogMenuInstanceId);
    $link = reset($links);
    $child_ids = $this->menuLinkManager->getChildIds($link->getPluginId());
    $return = [];

    foreach ($child_ids as $child_plugin_id) {
      // Pull the path from the menu link content.
      if (strpos($child_plugin_id, 'menu_link_content') === 0) {
        list(, $uuid) = explode(':', $child_plugin_id, 2);
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link_content */
        $menu_link_content = $this->entityRepository->loadEntityByUuid('menu_link_content', $uuid);
        $return[$menu_link_content->getWeight()] = $menu_link_content;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if (empty($this->collection) || empty($this->ogMenuInstanceId)) {
      return AccessResult::forbidden();
    }
    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    return Cache::mergeContexts($contexts, ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    return Cache::mergeTags($tags, ['ogmenu_instance:' . $this->ogMenuInstanceId]);
  }

}
