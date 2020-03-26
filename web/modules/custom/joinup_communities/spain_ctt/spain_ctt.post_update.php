<?php

/**
 * @file
 * Post update functions for the Spain CTT module.
 */

declare(strict_types = 1);

use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\user\Entity\User;

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

  $entity_type_manager = \Drupal::entityTypeManager();
  $rdf_entity_storage = $entity_type_manager->getStorage('rdf_entity');
  $node_storage = $entity_type_manager->getStorage('node');

  /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
  $membership_manager = \Drupal::service('og.membership_manager');

  $rdf_entities_to_delete = $nodes_to_delete = [];

  /** @var \Drupal\rdf_entity\RdfInterface[] $solutions */
  $solutions = $rdf_entity_storage->loadMultiple($solution_ids);
  foreach ($solutions as $solution) {
    $releases = $solution->field_is_has_version->referencedEntities();
    foreach ($releases as $release) {
      // Collect distributions of child releases.
      $rdf_entities_to_delete = array_merge($rdf_entities_to_delete, $release->field_isr_distribution->referencedEntities());
    }
    // Collect child releases.
    $rdf_entities_to_delete = array_merge($rdf_entities_to_delete, $releases);
    // Collect child distributions.
    $rdf_entities_to_delete = array_merge($rdf_entities_to_delete, $solution->field_is_distribution->referencedEntities());

    // Delete community content and custom pages.
    if ($community_content = $membership_manager->getGroupContentIds($solution, ['node'])) {
      $nodes_to_delete = array_merge($nodes_to_delete, $community_content['node']);
    }
  }
  // Collect the solutions themselves.
  $rdf_entities_to_delete = array_merge($rdf_entities_to_delete, $solutions);

  // Delete entities all together.
  $rdf_entity_storage->delete($rdf_entities_to_delete);
  $node_storage->delete($nodes_to_delete);
}

/**
 * Sets the owner of the "Spain CTT" collection to all its solutions.
 */
function spain_ctt_post_update_set_solutions_owner(array &$sandbox) {
  if (!isset($sandbox['solution_ids'])) {
    $id = 'http://administracionelectronica.gob.es/ctt';
    $spain_ctt = Rdf::load($id);
    $solutions = $spain_ctt->field_ar_affiliates->referencedEntities();

    $sandbox['solution_ids'] = array_map(function (RdfInterface $solution): string {
      return $solution->id();
    }, $solutions);
    $sandbox['owner_id'] = $spain_ctt->getOwnerId();
    $sandbox['processed'] = 0;
  }

  $solution_ids = array_splice($sandbox['solution_ids'], 0, 10);
  $user = User::load($sandbox['owner_id']);
  $roles = [
    OgRole::getRole('rdf_entity', 'solution', 'administrator'),
    OgRole::getRole('rdf_entity', 'solution', 'facilitator'),
  ];

  /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
  $membership_manager = \Drupal::service('og.membership_manager');
  foreach (Rdf::loadMultiple($solution_ids) as $solution) {
    if (!empty($membership_manager->getMembership($solution, $user->id(), OgMembershipInterface::ALL_STATES))) {
      continue;
    }
    $membership = $membership_manager->createMembership($solution, $user);
    $membership->setRoles($roles);
    $membership->save();

    $sandbox['processed']++;
  }

  $sandbox['#finished'] = (int) !$sandbox['solution_ids'];

  if ($sandbox['#finished'] === 1) {
    return "{$sandbox['processed']} memberships were appointed.";
  }
}
