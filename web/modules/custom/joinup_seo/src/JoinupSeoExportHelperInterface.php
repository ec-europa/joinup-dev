<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface JoinupSeoExportHelperInterface.
 */
interface JoinupSeoExportHelperInterface {

  /**
   * Exports the given RDF entity as JSON ready to be encapsulated to <head>.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The RDF entity to export.
   *
   * @return string
   *   The exported string;
   */
  public function exportRdfEntityMetadata(RdfInterface $entity): string;

}
