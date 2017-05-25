<?php

namespace Drupal\joinup_core;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to determine the human readable "page type" for the current request.
 *
 * This is used for visitor analytics, so that meaningful statistics can be
 * made according to the page type.
 *
 * Page types include content types (eg. "Collection", "Asset distribution",
 * "Discussion") and other notable pages such as "About page", "Contact form"
 * etc.
 */
class PageTypeDeterminer implements PageTypeDeterminerInterface, ContainerInjectionInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new PageTypeDeterminer object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.membership_manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $route_name = $this->routeMatch->getRouteName();
    switch ($route_name) {
      case 'entity.rdf_entity.canonical':
        return $this->getBundle($this->routeMatch->getParameter('rdf_entity'));
    }

    // Return the human readable alias for the route. If none exists, return the
    // route name itself.
    return $this->getRouteAlias($route_name) ?: $route_name;
  }

  /**
   * Returns the human readable bundle name of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to debundle.
   *
   * @return string
   *   The human readable bundle name.
   */
  protected function getBundle(EntityInterface $entity) {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());
    return $bundle_info[$entity->bundle()]['label'];
  }

  /**
   * Returns a human readable alias for a given route name.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return string|null
   *   The human readable alias, or NULL if no alias is defined.
   */
  protected static function getRouteAlias($route_name) {
    $aliases = self::getRouteAliases();
    if (!empty($aliases[$route_name])) {
      return $aliases[$route_name];
    }
    return NULL;
  }

  /**
   * Returns a list of human readable aliases for route names.
   *
   * @return array
   *   An associative array of human readable aliases, keyed by route name.
   */
  protected static function getRouteAliases() {
    return [
      'asset_release.solution_asset_release.overview' => 'Releases overview',
      'contact_form.contact_page' => 'Contact form',
      'dashboard.page' => 'Dashboard',
      'entity.rdf_entity.about_page' => 'About page',
      'entity.rdf_entity.edit_form' => 'Edit form',
      'entity.rdf_entity.rdf_export' => 'Export RDF metadata',
      'entity.user.canonical' => 'User profile',
      'homepage.content' => 'Homepage',
      'joinup_licence.overview' => 'Licence overview',
      'user.login' => 'Login form',
      'view.collections.page_1' => 'Collections overview',
      'view.content_overview.page_1' => 'Content overview',
      'view.solutions.page_1' => 'Solutions overview',
      'view.search.page_1' => 'Search',
    ];
  }

}
