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
use Drupal\og_menu\OgMenuInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MenuSubPages' block.
 *
 * @Block(
 *  id = "menu_sub_pages",
 *  admin_label = @Translation("Menu subpages"),
 * )
 */
class MenuSubPages extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The parent entity derived from the collection context.
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
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The ogmenu instance.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface
   */
  protected $ogMenuInstance;

  /**
   * Constructs a new MenuSubPages object.
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
   *   The Joinup relation manager service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $collection_context
   *   The collection route context.
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

    // Retrieve the og group from the context handled by og. The context value
    // is offered by the collection route context.
    // @see \Drupal\collection\ContextProvider\CollectionRouteContext.
    $collection_contexts = $collection_context->getRuntimeContexts(['og']);
    if ($collection_contexts && $collection_contexts['og']->hasContextValue()) {
      $this->collection = $collection_contexts['og']->getContextValue();
      $results = $this->membershipManager->getGroupContentIds($this->collection, ['ogmenu_instance']);

      if (!empty($results)) {
        $this->ogMenuInstance = $this->entityTypeManager->getStorage('ogmenu_instance')->load(reset($results['ogmenu_instance']));
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

    // Normally, this should be handled by blockAccess method but blockAccess is
    // being called every time and is not cached. It is faster to cache it by
    // returning an empty array here and using proper cache context and tags to
    // invalidate it.
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
      // The 'listing' child key is needed to avoid copying the #attributes to
      // the parent block.
      // @see \Drupal\block\BlockViewBuilder::preRender()
      'listing' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['listing', 'listing--grid', 'mdl-grid'],
        ],
      ],
    ];
    $build['listing'] += $items;

    return $build;
  }

  /**
   * Returns all child links of the current page's link in the ogmenu.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent[]
   *    An array of menu links.
   */
  protected function getChildLinks() {
    $links = $this->menuLinkManager->loadLinksByRoute($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), 'ogmenu-' . $this->ogMenuInstance->id());
    $link = reset($links);
    $child_ids = $this->menuLinkManager->getChildIds($link->getPluginId());
    $links = [];

    foreach ($child_ids as $child_plugin_id) {
      // Pull the path from the menu link content.
      if (strpos($child_plugin_id, 'menu_link_content') === 0) {
        list(, $uuid) = explode(':', $child_plugin_id, 2);
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link_content */
        $menu_link_content = $this->entityRepository->loadEntityByUuid('menu_link_content', $uuid);
        $links[$menu_link_content->getWeight()] = $menu_link_content;
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   *
   * The block should not be shown if there is no collection context or if the
   * collection does not have an ogMenuInstance created. The second case should
   * not normally occur as the ogMenuInstance is being created automatically for
   * the collection so it is merely to ensure that the page will not break.
   */
  protected function blockAccess(AccountInterface $account) {
    if (empty($this->collection) || empty($this->ogMenuInstance)) {
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
    return Cache::mergeTags($tags, $this->ogMenuInstance->getCacheTags());
  }

}
