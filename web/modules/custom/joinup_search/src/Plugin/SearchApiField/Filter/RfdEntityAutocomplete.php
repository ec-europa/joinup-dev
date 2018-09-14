<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\SearchApiField\Filter;

/**
 * A filter plugin that allows to choose a single rdf entity.
 *
 * @SearchApiFieldFilter(
 *   id = "rdf_entity_autocomplete",
 *   label = @Translation("Rdf entity label autocomplete"),
 *   fields = {
 *     "id"
 *   }
 * )
 */
class RfdEntityAutocomplete extends EntityAutocompleteBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityType(): string {
    return 'rdf_entity';
  }

}
