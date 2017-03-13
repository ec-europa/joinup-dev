<?php

namespace Drupal\collection\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Sets the current collection as a context on collection routes.
 *
 * The current collection can be the entity that the current route belongs to
 * or the owner of the current entity - which is an og content of this
 * collection.
 * First check occurs on the route object from the current route. This way
 * all relative paths to the collection are covered whether it is a canonical
 * or an edit view.
 * If no context is found there on the current page, then another attempt is
 * performed to retrieve the context from the current entity's owner entity.
 * To achieve this, every parameter is being checked on whether the entity is
 * a group content entity. If true and the parent entity is a collection, then
 * the collection becomes the active context.
 *
 * The routes that the context is available is also filtered to prevent
 * unexpected context availability.
 */
class CollectionRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new CollectionRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   */
  public function __construct(RouteMatchInterface $route_match, MembershipManagerInterface $membership_manager) {
    $this->routeMatch = $route_match;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['rdf_entity'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      if ($collection = $this->routeMatch->getParameter('rdf_entity')) {
        if ($collection->bundle() == 'collection') {
          $value = $collection;
        }
      }
    }
    elseif (($route_parameters = $this->routeMatch->getParameters()) && in_array($this->routeMatch->getRouteName(), $this->getSupportedRoutes())) {
      foreach ($route_parameters as $route_parameter) {
        if ($route_parameter instanceof ContentEntityInterface) {
          $bundle = $route_parameter->bundle();
          $entity_type = $route_parameter->getEntityTypeId();

          // Check if the object is a og content entity.
          if (Og::isGroupContent($entity_type, $bundle) && ($groups = $this->membershipManager->getGroupIds($route_parameter, 'rdf_entity', 'collection'))) {
            // A content can belong to only one rdf_entity.
            // Check that the content is not an orphaned one.
            if ($collection_id = reset($groups['rdf_entity'])) {
              $collection = Rdf::load($collection_id);
              $value = $collection;
            }
          }
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $collection_context_definition = new ContextDefinition('entity', $this->t('Organic group provided by collection'), FALSE);
    $context = new Context($collection_context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['og'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $og_context = new Context(new ContextDefinition('og', $this->t('Collection from URL')));
    return ['og' => $og_context];
  }

  /**
   * Returns an array of allowed routes that the context should be available at.
   *
   * @return array
   *   An array of routes.
   */
  protected function getSupportedRoutes() {
    return [
      'rdf_entity.canonical',
      'entity.node.canonical',
      'collection.leave_confirm_form',
    ];
  }

}
