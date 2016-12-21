<?php

namespace Drupal\rdf_file\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\file\Entity\File;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "rdf_file_default",
 *   label = @Translation("RDF File selection"),
 *   group = "rdf_file_default",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class RdfFileSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $results = [];
    $found = [];

    if ($ids) {
      $query = $this->buildEntityQuery();
      $results = $query
        ->condition('uri', $ids, 'IN')
        ->execute();
    }
    foreach ($results as $result) {
      $file = File::load($result);
      $uri = $file->getFileUri();
      if (in_array($uri, $ids)) {
        $found[] = $uri;
      }
    }

    return $found;
  }

}
