<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eif\EifInterface;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Defines a argument validator plugin for the EIF Toolbox entity.
 *
 * @ViewsArgumentValidator(
 *   id = "eif_toolbox",
 *   title = @Translation("EIF Toolbox"),
 *   entity_type = "rdf_entity"
 * )
 */
class EifArgumentValidator extends Entity {

  /**
   * {@inheritdoc}
   */
  protected function validateEntity(EntityInterface $entity) {
    return ($entity->id() === EifInterface::EIF_ID) && parent::validateEntity($entity);
  }

}
