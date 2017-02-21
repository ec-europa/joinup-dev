<?php

namespace Drupal\solution\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SolutionContentBlock' demonstration block.
 *
 * This is to provide visibility to content that belong to the solution group.
 *
 * @Block(
 *  id = "solution_content_block",
 *  admin_label = @Translation("Solution relations"),
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
   * The route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   *
   * @todo EntityManager is deprecated. Use EntityTypeManager instead.
   *
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2669
   */
  protected $entityManager;

  /**
   * Constructs a SolutionContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route matcher.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The deprecated entity manager.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $solution_context
   *   The context provider for the solution context.
   *
   * @todo EntityManager is deprecated. Use EntityTypeManager instead.
   *
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2669
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $current_route_match, EntityManager $entity_manager, ContextProviderInterface $solution_context) {
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
    // @todo EntityManager is deprecated. Use EntityTypeManager instead.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2669
    $entities = $this->entityManager->getStorage('node')->loadByProperties([OgGroupAudienceHelperInterface::DEFAULT_FIELD => $this->solution->id()]);
    $items = [];
    foreach ($entities as $entity) {
      $items[] = ['#markup' => $entity->link()];
    }

    // Also retrieve related collections.
    $ids = $this->entityManager->getStorage('rdf_entity')->getQuery()
      ->condition('field_ar_affiliates', $this->solution->id())
      ->execute();
    $entities = Rdf::loadMultiple($ids);
    foreach ($entities as $entity) {
      $items[] = ['#markup' => $entity->link()];
    }

    // Build the array output.
    if ($items) {
      return [
        'list' => [
          '#theme' => 'item_list',
          '#items' => $items,
          '#cache' => [
            'tags' => [
              'entity:node:news',
              'entity:rdf_entity:collection',
            ],
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
