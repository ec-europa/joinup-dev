<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Row;

/**
 * Default implementation of RedirectImportInterface methods for files.
 *
 * @see \Drupal\joinup_migrate\RedirectImportInterface
 */
trait DefaultFileRedirectTrait {

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri(EntityInterface $entity) {
    /** @var \Drupal\file\FileInterface $entity */
    // Such redirects are not cleared automatically by the Redirect module, when
    // the source file entity is deleted. Thus, we are fulfilling this task in
    // our custom module, in joinup_core_file_delete().
    // @see joinup_core_file_delete()
    return 'base:/sites/default/files/' . file_uri_target($entity->getFileUri());
  }

}
