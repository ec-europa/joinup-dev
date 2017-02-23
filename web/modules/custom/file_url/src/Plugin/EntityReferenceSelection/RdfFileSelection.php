<?php

namespace Drupal\file_url\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "file_url_default",
 *   label = @Translation("File URL selection"),
 *   group = "file_url_default",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class RdfFileSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    /** @var \Drupal\file_url\FileUrlHandler $file_handler */
    $file_handler = \Drupal::service('file_url.handler');
    $found = [];

    foreach ($ids as $id) {
      $file = $file_handler::urlToFile($id);
      // External file are always found.
      if ($file_handler->isRemote($file)) {
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
