<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\solution\Entity\SolutionInterface;

/**
 * Remove a selected set of solutions and 1 document from Eurostat.
 */
function joinup_core_post_update_0106201(): void {
  /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $rdf_storage */
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  /** @var \Drupal\collection\Entity\CollectionInterface $collection */
  $collection = $rdf_storage->load('http://data.europa.eu/w21/01b52000-c0ba-4e64-a32c-79557a743462');
  $solutions = array_filter($collection->getSolutions(), function (SolutionInterface $solution) {
    // Remove all solutions starting with the word SCL.
    if (substr($solution->label(), 0, 4) === 'SCL ') {
      return TRUE;
    }

    // Additionally remove 3 specific solutions.
    return in_array($solution->id(), [
      // 'General information on the "Standard code lists" project'.
      'http://ec.europa.eu/eurostat/ramon/miscellaneous/index.cfm?TargetUrl=DSP_GENINFO_SCL',
      // 'Eurostat SDMX Converter'.
      'http://data.europa.eu/w21/35ef35c6-a530-4821-8c52-8d796ec86a1d',
      // 'Eurostat XBRL Reference Architecture'.
      'http://data.europa.eu/w21/96f74e35-abda-4475-8b2d-22fed2120fc5',
    ]);
  });

  $total = count($solutions);
  $count = 0;

  foreach ($solutions as $solution) {
    \Drupal::logger('joinup')->notice('[%count/%total] Deleting solution %title', [
      '%count' => ++$count,
      '%total' => $total,
      '%title' => $solution->label(),
    ]);
    $solution->delete();
  }

  // Delete the document 'XBRL_Architecture_v2_0_en.doc'.
  /** @var \Drupal\node\NodeStorageInterface $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  /** @var \Drupal\joinup_document\Entity\DocumentInterface $document */
  $document = $node_storage->load(41745);
  if (!empty($document)) {
    \Drupal::logger('joinup')->notice('[1/1] Deleting document %title', [
      '%title' => $document->label(),
    ]);
    $document->delete();
  }
}
