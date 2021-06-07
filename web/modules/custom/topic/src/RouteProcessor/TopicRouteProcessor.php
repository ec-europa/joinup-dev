<?php

declare(strict_types = 1);

namespace Drupal\topic\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Symfony\Component\Routing\Route;

/**
 * Flags the route of canonical topic pages to be redirected to the search.
 *
 * Topic pages are planned but not yet ready. Until they are we direct the user
 * to the search page, with the topic pre-filtered.
 */
class TopicRouteProcessor implements OutboundRouteProcessorInterface {

  /**
   * Route option indicating that the URI should be redirected to the search.
   */
  public const REDIRECT_TO_SEARCH_PAGE_OPTION = 'topic_redirect_to_search';

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, ?BubbleableMetadata $bubbleable_metadata = NULL): void {
    if ($route_name !== 'entity.taxonomy_term.canonical') {
      return;
    }

    // Set a flag on the route that it needs to be redirected.
    // @see \Drupal\topic\PathProcessor\TopicPathProcessor::processOutbound()
    $route->setOption(self::REDIRECT_TO_SEARCH_PAGE_OPTION, TRUE);
  }

}
