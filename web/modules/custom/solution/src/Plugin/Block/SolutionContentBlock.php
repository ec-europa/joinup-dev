<?php

namespace Drupal\solution\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityManager;

/**
 * Provides a 'SolutionContentBlock' demonstration block.
 *
 * This is to provide visibility to content that belong to the solution group.
 *
 * @Block(
 *  id = "solution_content_block",
 *  admin_label = @Translation("Solution content"),
 * )
 */
class SolutionContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The solution.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $solution;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match, EntityManager $entity_manager, ContextProviderInterface $solution_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
    if (!empty($solution_context->getRuntimeContexts(['solution'])['solution'])) {
      $this->solution = $solution_context->getRuntimeContexts(['solution'])['solution']->getContextValue();
    }
    $this->entityManager = $entity_manager;
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
      $container->get('entity.manager'),
      $container->get('solution.solution_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // If the page is not a solution page, return an empty form so that the
    // rendering of this block can be omitted.
    if (empty($this->solution) || $this->solution->bundle() != 'solution') {
      return [];
    }

    // Get news referencing to this solution.
    $entities = $this->entityManager->getStorage('node')
      ->loadByProperties([OgGroupAudienceHelper::DEFAULT_FIELD => $this->solution->id()]);
    $items = [];
    foreach ($entities as $entity) {
      $items[] = array('#markup' => $entity->link());
    }
    if ($items) {
      return [
        'list' => [
          '#theme' => 'item_list',
          '#items' => $items,
          '#cache' => [
            'tags' => ['entity:node:news'],
          ],
        ],
      ];
    }
    return [];
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
    return (($this->currentRouteMatch->getRouteName() == 'entity.rdf_entity.canonical') && ($this->solution instanceof RdfInterface)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
