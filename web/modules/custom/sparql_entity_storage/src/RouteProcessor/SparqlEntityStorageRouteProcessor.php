<?php

namespace Drupal\sparql_entity_storage\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\UriEncoder;
use Symfony\Component\Routing\Route;

/**
 * Escapes the uri with a user friendly replacement group of characters.
 *
 * @see \Drupal\sparql_entity_storage\ParamConverter\SparqlEntityStorageConverter
 * @see \Drupal\sparql_entity_storage\UrlEncoder
 */
class SparqlEntityStorageRouteProcessor implements OutboundRouteProcessorInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($route->hasOption('parameters')) {
      foreach ($route->getOption('parameters') as $type => $parameter) {
        // If the converter exists in the parameter, then the parameter needs to
        // be normalized.
        if (isset($parameter['converter']) && $parameter['converter'] === 'sparql.paramconverter' && SparqlArg::isValidResource($parameters[$type])) {
          $parameters[$type] = UriEncoder::encodeUrl($parameters[$type]);
        }
      }
    }
  }

}
