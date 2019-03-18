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
  $id = 'http://administracionelectronica.gob.es/ctt';
  if (empty(Rdf::load($id))) {
    return;
  }

  require __DIR__ . '/includes/remove_duplicates.inc';

  // Create the original duplicates in case they do not exist.
  _spain_ctt_duplicates_create_duplicates();

  // Move all content from the duplicated solutions to the original one.
  _spain_ctt_duplicates_merge_content();

  // Delete duplicates.
  _spain_ctt_duplicates_delete_duplicates();

  // Update the ctt collection relationships.
  _spain_ctt_duplicates_collection_relations();

  // For the below entities, now that their duplicates are cleaned, rebuild the
  // url alias to remove the unnecessary suffix.
  $entity_ids = [
    'http://administracionelectronica.gob.es/ctt/archive',
    'http://administracionelectronica.gob.es/ctt/documentoe',
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

/**
 * Fixes the associatedWith provenance property for federated entities.
 */
function spain_ctt_post_update_fix_associated_with() {
  $query = <<<QUERY
    WITH <http://joinup.eu/provenance_activity>
    INSERT { ?s <http://www.w3.org/ns/prov#wasAssociatedWith> <http://administracionelectronica.gob.es/ctt> }
    WHERE { ?s a <http://www.w3.org/ns/prov#Activity> FILTER NOT EXISTS { ?s <http://www.w3.org/ns/prov#wasAssociatedWith> ?object } }
QUERY;

  $connection = \Drupal::service('sparql_endpoint');
  $connection->query($query);
}

/**
 * Delete unwanted ctt entities.
 */
function spain_ctt_post_update_delete_ctt_entities() {
  $solution_ids = [
    'http://administracionelectronica.gob.es/ctt/dir3caib_1',
    'http://administracionelectronica.gob.es/ctt/lucia_1',
    'http://administracionelectronica.gob.es/ctt/censoelectoral',
    'http://administracionelectronica.gob.es/ctt/pasareladepagos',
    'http://administracionelectronica.gob.es/ctt/arcas',
    'http://administracionelectronica.gob.es/ctt/autentica_1',
    'http://administracionelectronica.gob.es/ctt/compartircursosonline',
    'http://administracionelectronica.gob.es/ctt/adise',
    'http://administracionelectronica.gob.es/ctt/cursosonline',
    'http://administracionelectronica.gob.es/ctt/registra',
    'http://administracionelectronica.gob.es/ctt/plantillaspge',
    'http://administracionelectronica.gob.es/ctt/sipas',
    'http://administracionelectronica.gob.es/ctt/publiges',
    'http://administracionelectronica.gob.es/ctt/tienda',
    'http://administracionelectronica.gob.es/ctt/gesval',
    'http://administracionelectronica.gob.es/ctt/gama',
    'http://administracionelectronica.gob.es/ctt/gades',
    'http://administracionelectronica.gob.es/ctt/gpf',
    'http://administracionelectronica.gob.es/ctt/allopd',
    'http://administracionelectronica.gob.es/ctt/aplica_1',
    'http://administracionelectronica.gob.es/ctt/aplica',
    'http://administracionelectronica.gob.es/ctt/estadisticatrama',
    'http://administracionelectronica.gob.es/ctt/grial',
    'http://administracionelectronica.gob.es/ctt/encuestaenlinea',
    'http://administracionelectronica.gob.es/ctt/alefacil',
    'http://administracionelectronica.gob.es/ctt/alweb',
    'http://administracionelectronica.gob.es/ctt/alpadron',
    'http://administracionelectronica.gob.es/ctt/ces',
    'http://administracionelectronica.gob.es/ctt/buzon060',
    'http://administracionelectronica.gob.es/ctt/extra',
    'http://administracionelectronica.gob.es/ctt/ticketing',
    'http://administracionelectronica.gob.es/ctt/sorolla',
    'http://administracionelectronica.gob.es/ctt/road',
    'http://administracionelectronica.gob.es/ctt/red060',
    'http://administracionelectronica.gob.es/ctt/note',
    'http://administracionelectronica.gob.es/ctt/pac',
    'http://administracionelectronica.gob.es/ctt/proa',
    'http://administracionelectronica.gob.es/ctt/gesfoge',
    'http://administracionelectronica.gob.es/ctt/gesnote',
    'http://administracionelectronica.gob.es/ctt/foge',
    'http://administracionelectronica.gob.es/ctt/docuconta',
    'http://administracionelectronica.gob.es/ctt/canoa',
    'http://administracionelectronica.gob.es/ctt/tramitador',
  ];
  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

  foreach ($solution_ids as $solution_id) {
    $solution = $storage->load($solution_id);
    // Solutions in CTT have maximum one direct reference to distributions
    // and no reference to a release.
    // These solutions also do not have any community content.
    if ($distribution_id = $solution->field_is_distribution->target_id) {
      $distribution = $storage->load($distribution_id);
      $distribution->delete();
    }
    $solution->delete();
  }
}
