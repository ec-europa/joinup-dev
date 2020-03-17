<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access to manage solutions.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "manage_solutions",
 *   title = @Translation("Manage solutions"),
 *   help = @Translation("Access will be granted to users with 'manage solutions' permission."),
 * )
 */
class ManageSolutionsAccess extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ResettableStackedRouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route
      ->setRequirement('_permission', 'manage solutions')
      ->setRequirement('_joinup_group', 'collection');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    if (!$account->hasPermission('manage solutions')) {
      return FALSE;
    }

    if ($id = $this->routeMatch->getRawParameter('rdf_entity')) {
      $ids = $this->entityTypeManager->getStorage('rdf_entity')->getQuery()
        ->condition('rid', 'collection')
        ->condition('id', UriEncoder::decodeUrl($id))
        ->execute();
      if ($ids) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
