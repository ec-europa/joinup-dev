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
function joinup_core_post_update_0106300(array &$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager();

  // Gather the IDs of the solutions that need to be removed, according to
  // client specifications.
  if (!isset($sandbox['ids'])) {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $rdf_storage */
    $rdf_storage = $entity_type_manager->getStorage('rdf_entity');

    /** @var \Drupal\collection\Entity\CollectionInterface $collection */
    $collection = $rdf_storage->load('http://data.europa.eu/w21/01b52000-c0ba-4e64-a32c-79557a743462');

    $solution_ids = array_filter($collection->getSolutionIds(), function (string $solution_id) {
      // Remove "SCL" solutions. They are recognizable by having a particular
      // pattern in the RDF ID.
      if (strpos($solution_id, '&StrNom=CL_') !== FALSE) {
        return TRUE;
      }

      // Additionally remove 3 specific solutions.
      return in_array($solution_id, [
        // 'General information on the "Standard code lists" project'.
        'http://ec.europa.eu/eurostat/ramon/miscellaneous/index.cfm?TargetUrl=DSP_GENINFO_SCL',
        // 'Eurostat SDMX Converter - 2 solutions with the same entity exist'.
        'http://data.europa.eu/w21/35ef35c6-a530-4821-8c52-8d796ec86a1d',
        'https://circabc.europa.eu/w/browse/ea54c8ee-5fe8-431d-826e-5e1a2835d405',
        // 'Eurostat XBRL Reference Architecture'.
        'http://data.europa.eu/w21/96f74e35-abda-4475-8b2d-22fed2120fc5',
      ]);
    });

    // Add the solutions to the list as {entity_id} => {entity_type} pairs.
    $sandbox['ids'] = array_map(function (string $id) {
      return [$id, 'rdf_entity'];
    }, $solution_ids);

    // Also add the document 'XBRL_Architecture_v2_0_en.doc' to the list.
    $sandbox['ids'][] = [41745, 'node'];

    // Initialize counters.
    $sandbox['progress'] = 0;
    $sandbox['total'] = count($sandbox['ids']);
  }

  $to_process = array_splice($sandbox['ids'], 0, 10);
  foreach ($to_process as $pair) {
    $sandbox['progress']++;

    [$id, $entity_type] = $pair;
    $entity = $entity_type_manager->getStorage($entity_type)->load($id);

    // Check if the solution still exists. It might have been deleted in a
    // previous run.
    if (!$entity instanceof SolutionInterface) {
      \Drupal::logger('joinup')->notice('[%count/%total] Skipping already deleted %type', [
        '%count' => sprintf('%03d', $sandbox['progress']),
        '%total' => sprintf('%03d', $sandbox['total']),
        '%type' => $entity_type === 'rdf_entity' ? 'solution' : 'document',
      ]);
      continue;
    }

    \Drupal::logger('joinup')->notice('[%count/%total] Deleting %type %title', [
      '%count' => sprintf('%03d', $sandbox['progress']),
      '%total' => sprintf('%03d', $sandbox['total']),
      '%type' => $entity_type === 'rdf_entity' ? 'solution' : 'document',
      '%title' => $entity->label(),
    ]);

    $entity->delete();
  }

  // Do intermediate deletion of orphaned group content. Otherwise all of them
  // will be deleted on shutdown and this may cause PHP to run out of memory.
  \Drupal::logger('joinup')->notice('Cleaning up orphaned group content. One moment please.');
  /** @var \Drupal\og\OgDeleteOrphansPluginManager $queue_worker_manager */
  $queue_worker_manager = \Drupal::service('plugin.manager.og.delete_orphans');
  /** @var \Drupal\og\OgDeleteOrphansInterface $queue_worker */
  $queue_worker = $queue_worker_manager->createInstance('simple');
  $queue_worker->process();

  $sandbox['#finished'] = !empty($sandbox['ids']) ? $sandbox['progress'] / $sandbox['total'] : 1;
}
