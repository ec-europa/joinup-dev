<?php

declare(strict_types = 1);

namespace Drupal\rdf_taxonomy\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides an alternative entity class for 'taxonomy_term'.
 */
class RdfTerm extends Term {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $base_fields = parent::baseFieldDefinitions($entity_type);
    $base_fields['status']->setCustomStorage(TRUE);

    // Don't support taxonomy term revisions.
    unset(
      $base_fields['revision_default'],
      $base_fields['revision_translation_affected'],
      $base_fields['revision_created'],
      $base_fields['revision_user'],
      $base_fields['revision_log_message']
    );

    return $base_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    // The RDF taxonomy term doesn't support the published flag.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    // The RDF taxonomy term doesn't support the published flag.
    return $this;
  }

}
