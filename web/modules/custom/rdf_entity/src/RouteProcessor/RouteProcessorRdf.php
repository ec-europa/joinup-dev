<?php

namespace Drupal\rdf_entity\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use EasyRdf\Serialiser\Ntriples;
use Symfony\Component\Routing\Route;

/**
 * Provides a route processor to replace '/' symbols for uri ids.
 *
 * This is the counterpart for the RdfEntityConverter class.
 *
 * @See \Drupal\rdf_entity\ParamConverter\RdfEntityConverter
 */
class RouteProcessorRdf implements OutboundRouteProcessorInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Ntriples serialize class.
   *
   * @var \EasyRdf\Serialiser\Ntriples
   */
  protected $serializer;

  /**
   * Constructs a new RouteProcessorRdf.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
    $this->serializer = new Ntriples();
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($route->hasOption('parameters')) {
      foreach ($route->getOption('parameters') as $type => $parameter) {
        // If the rdf_entity converter exists in the parameter,
        // then the parameter is of type rdf_entity and needs to be normalized.
        if (isset($parameter['converter']) && $parameter['converter'] == 'paramconverter.rdf_entity') {
          $parameters[$type] = str_replace('/', '\\', $parameters[$type]);
        }
      }
    }
  }

}
