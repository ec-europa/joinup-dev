<?php

namespace Drupal\rdf_draft;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Provides dynamic permissions for rdf graphs.
 */
class RdfGraphPermissions {
  use StringTranslationTrait;

  /**
   * Returns an array of graph view permissions.
   *
   * @return array
   *   The rdf graph view permissions.
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function rdfGraphPermissions() {
    $perms = array();
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      if ($storage instanceof RdfEntitySparqlStorage) {
        $definitions = $storage->getGraphsDefinition();
        unset($definitions['default']);
        foreach ($definitions as $name => $definition) {
          $perms += $this->buildPermissions($entity_type, $name);
        }
      }
    }

    return $perms;
  }

  /**
   * Returns a list of permissions per entity type and graph.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $type
   *   The entity type.
   * @param string $graph
   *   The machine name for the graph.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityTypeInterface $type, $graph) {
    $type_id = $type->id();
    $type_params = array(
      '%type_name' => $type->getLabel(),
      '%graph_name' => $graph,
    );

    return array(
      "view $type_id $graph graph" => array(
        'title' => $this->t('%type_name: View %graph_name graph', $type_params),
      ),
    );
  }

}
