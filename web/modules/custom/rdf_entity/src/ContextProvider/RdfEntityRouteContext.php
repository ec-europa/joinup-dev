<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Sets the current rdf entity as a context on rdf entity routes.
 */
class RdfEntityRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RdfEntityRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::fromEntityTypeId('rdf_entity');
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['rdf_entity'])) {
      if ($rdf_entity = $this->routeMatch->getParameter('rdf_entity')) {
        $value = $rdf_entity;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'rdf_entity.rdf_add') {
      $rdf_type = $this->routeMatch->getParameter('rdf_type');
      $value = Rdf::create(['rid' => $rdf_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['rdf_entity'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(EntityContextDefinition::fromEntityTypeId('rdf_entity'));
    return ['rdf_entity' => $context];
  }

}
