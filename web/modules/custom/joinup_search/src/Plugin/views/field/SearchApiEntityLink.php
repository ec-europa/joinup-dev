<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\views\field;

use Drupal\search_api\Plugin\views\field\SearchApiFieldTrait;
use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_entity_link")
 */
class SearchApiEntityLink extends EntityLink {

  use SearchApiFieldTrait;

}
