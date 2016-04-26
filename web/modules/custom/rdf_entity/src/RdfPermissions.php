<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Provides dynamic permissions for RdfEntities of different types.
 */
class RdfPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The RdfEntity type permissions.
   */
  public function rdfTypePermissions() {
    $perms = array();
    // Generate node permissions for all node types.
    foreach (RdfEntityType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\rdf_entity\Entity\RdfEntityType $type
   *   The RdfEntity type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(RdfEntityType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id rdf entity" => array(
        'title' => $this->t('%type_name: Create new rdf entity', $type_params),
      ),
      "edit $type_id rdf entity" => array(
        'title' => $this->t('%type_name: Edit rdf entity', $type_params),
      ),
      "delete $type_id rdf entity" => array(
        'title' => $this->t('%type_name: Delete rdf entity', $type_params),
      ),
    );
  }

}
