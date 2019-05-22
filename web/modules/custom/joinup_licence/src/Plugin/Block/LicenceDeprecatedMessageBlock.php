<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a deprecation warning in a block.
 *
 * @Block(
 *  id = "licence_deprecated_message_block",
 *  admin_label = @Translation("Licence deprecated term message"),
 * )
 */
class LicenceDeprecatedMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
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
   * Constructs a new LicenceDeprecatedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $route_name = $this->currentRouteMatch->getRouteName();
    if ($route_name !== 'entity.rdf_entity.canonical') {
      return [];
    }

    $term = $this->currentRouteMatch->getParameter('rdf_entity');
    if (empty($term) || $term->bundle() !== 'licence') {
      return [];
    }

    $deprecated = (bool) $term->get('field_licence_deprecated')->value;
    if (!$deprecated) {
      return [];
    }
    $message = $this->t('This licence is deprecated and will not be selected for new distributions.');
    $build = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [$message],
      ],
      '#status_headings' => [
        'warning' => $this->t('Warning message'),
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    return Cache::mergeContexts($cache_contexts, ['route']);
  }

}
