<?php

namespace Drupal\rdf_file\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\rdf_file\Entity\RemoteFile;

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
    /** @var \Drupal\rdf_file\RdfFileHandler $file_handler */
    $file_handler = \Drupal::service('rdf_file.handler');
    $found = [];

    foreach ($ids as $id) {
      $file = $file_handler->UrlToFile($id);
      // External file are always found.
      if ($file instanceof RemoteFile) {
        $uri = $file->getFileUri();
      }
      else {
        $uri = $file_handler::fileToUrl($file);
      }

      if (in_array($uri, $ids)) {
        $found[] = $uri;
      }
    }
    return $found;
  }

}
