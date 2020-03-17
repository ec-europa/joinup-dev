<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Wraps the "string" argument handler in order to decode the RDF entity ID.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("og_audience_group")
 */
class StringGroupArgument extends StringArgument {

  use DecodeRdfEntityIdArgumentTrait;

}
