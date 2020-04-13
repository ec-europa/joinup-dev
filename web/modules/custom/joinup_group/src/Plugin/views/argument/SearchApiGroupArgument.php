<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\argument;

use Drupal\search_api\Plugin\views\argument\SearchApiStandard;

/**
 * Wraps the Search API argument handler in order to decode the RDF entity ID.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_group")
 */
class SearchApiGroupArgument extends SearchApiStandard {

  use DecodeRdfEntityIdArgumentTrait;

}
