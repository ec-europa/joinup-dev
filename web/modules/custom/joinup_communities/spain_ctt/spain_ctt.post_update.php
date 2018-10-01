<?php

/**
 * @file
 * Post update functions for the Spain CTT module.
 */

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\redirect\Entity\Redirect;

/**
 * Clean more solution duplicates in the ctt collection.
 */
function spain_ctt_post_update_clean_ctt_duplicates() {
  // The list of entries to be cleaned has been updated. We only need to call
  // the install function again.
  require __DIR__ . '/spain_ctt.install';
  spain_ctt_install();

  // For the below entities, now that their duplicates are cleaned, rebuild the
  // url alias to remove the unnecessary suffix.
  $entity_ids = [
    'http://administracionelectronica.gob.es/ctt/archive',
    'http://administracionelectronica.gob.es/ctt/eemgde',
    'http://administracionelectronica.gob.es/ctt/regfia',
    'http://administracionelectronica.gob.es/ctt/dscp',
    'http://administracionelectronica.gob.es/ctt/pau',
    'http://administracionelectronica.gob.es/ctt/pfiaragon',
    'http://administracionelectronica.gob.es/ctt/dir3',
    'http://administracionelectronica.gob.es/ctt/svd',
    'http://administracionelectronica.gob.es/ctt/scsp',
    'http://administracionelectronica.gob.es/ctt/tsa',
    'http://administracionelectronica.gob.es/ctt/afirma',
    'http://administracionelectronica.gob.es/ctt/codice',
    'http://administracionelectronica.gob.es/ctt/badaral',
  ];

  /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
  $pathauto_generator = \Drupal::service('pathauto.generator');
  /** @var \Drupal\Core\Entity\EntityInterface $entity */
  foreach (Rdf::loadMultiple($entity_ids) as $entity) {
    $old_alias = $entity->toUrl()->toString();
    $pathauto_generator->updateEntityAlias($entity, 'update');
    $new_alias = $entity->toUrl()->toString();
    Redirect::create([
      'redirect_source' => $old_alias,
      'redirect_redirect' => $new_alias,
      'language' => 'und',
      'status_code' => '301',
    ])->save();
  }
}

/**
 * Enable pipeline, joinup_federation and rdf_entity_provenance modules.
 */
function spain_ctt_post_update_enable_pipeline_modules() {
  \Drupal::service('module_installer')->install(['joinup_federation']);
}
