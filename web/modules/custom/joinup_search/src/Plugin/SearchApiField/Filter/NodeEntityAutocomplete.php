<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\SearchApiField\Filter;

/**
 * A filter plugin that allows to choose a single node entity.
 *
 * @SearchApiFieldFilter(
 *   id = "node_entity_autocomplete",
 *   label = @Translation("Node entity label autocomplete"),
 *   fields = {
 *     "nid"
 *   }
 * )
 */
class NodeEntityAutocomplete extends EntityAutocompleteBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityType(): string {
    return 'node';
  }

}
