<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_entity_link")
 */
class SearchApiEntityLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $values) {
    if (isset($values->_object) && $values->_object->getValue() instanceof EntityInterface) {
      return $values->_object->getValue();
    }

    return NULL;
  }

}
