<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_entity\Traits\DrupalTestTraits;

use Drupal\Tests\rdf_entity\Traits\RdfEntityCreationTrait as ModuleCreationTrait;
use Drupal\rdf_entity\RdfInterface;

/**
 * Adds Drupal Test Traits (DTT) support for RDF Entity.
 *
 * @see https://gitlab.com/weitzman/drupal-test-traits
 */
trait RdfEntityCreationTrait {

  use ModuleCreationTrait {
    createRdfEntity as moduleCreateRdfEntity;
  }

  /**
   * Creates an RDF Entity and marks it for automatic cleanup.
   *
   * @param array $values
   *   The entity creation values.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   An RDF entity marked for creation.
   */
  protected function createRdfEntity(array $values = []): RdfInterface {
    $rdf_entity = $this->moduleCreateRdfEntity($values);
    $this->markEntityForCleanup($rdf_entity);
    return $rdf_entity;
  }

}
