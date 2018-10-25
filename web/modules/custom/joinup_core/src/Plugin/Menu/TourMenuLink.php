<?php

namespace Drupal\joinup_core\Plugin\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom menu link for the tour support menu item.
 */
class TourMenuLink extends MenuLinkDefault {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Creates a new menu link instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $current_route_match
   *   The current route match.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, EntityTypeManagerInterface $entity_type_manager, ResettableStackedRouteMatchInterface $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    // If we're administering menus, fallback to the default behaviour.
    $route_path = ltrim($this->currentRouteMatch->getRouteObject()->getPath(), '/');
    if (strpos($route_path, 'admin/structure/menu/') === 0) {
      return parent::isEnabled();
    }

    $route_name = $this->currentRouteMatch->getRouteName();
    $route_parameters = $this->currentRouteMatch->getRawParameters()->all();

    $tour_storage = $this->entityTypeManager->getStorage('tour');
    /** @var \Drupal\tour\TourInterface[] $tours */
    $tours = $tour_storage->loadByProperties(['status' => TRUE]);
    foreach ($tours as $tour_id => $tour) {
      // This tour is configured to show up on this route.
      if ($tour->hasMatchingRoute($route_name, $route_parameters)) {
        // The link could be disabled from the admin interface.
        return parent::isEnabled();
      }
    }

    // No tour has been configured for this route.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(['route.name'], parent::getCacheContexts());
  }

}
