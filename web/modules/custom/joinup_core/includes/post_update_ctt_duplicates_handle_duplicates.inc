<?php

/**
 * @file
 * Contains update functions related to duplicates of the ctt community.
 */

use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Create original entities for CTT.
 */
function _joinup_core_post_update_ctt_duplicates_create_duplicates() {
  // During migration, it might be that among the duplicates, the wrong version
  // was maintained. Create a clone of the duplicate entity to use as the
  // original.
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicate_ids) {
    $group = Rdf::load($original_id);
    // In case the original entity is not found, it means that the entity with
    // the duplicate id was kept after the migration.
    if (!empty($group)) {
      continue;
    }

    foreach ($duplicate_ids as $duplicate_id) {
      if (!($duplicate_group = Rdf::load($duplicate_id))) {
        continue;
      }
      break;
    }

    if (empty($duplicate_group)) {
      continue;
    }

    $original_group = clone $duplicate_group;
    $original_group->set('id', $original_id);
    $original_group->enforceIsNew();
    // Unset related distributions so that they are moved later properly.
    $original_group->set('field_is_distribution', NULL);
    $original_group->skip_notification = TRUE;
    $original_group->save();
  }
}

/**
 * Move content from duplicates to the original entity.
 */
function _joinup_core_post_update_ctt_duplicates_merge_content() {
  /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
  $membership_manager = \Drupal::service('og.membership_manager');
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicates_ids) {
    $original_group = Rdf::load($original_id);
    if (empty($original_group)) {
      continue;
    }

    foreach ($duplicates_ids as $duplicate_id) {
      $duplicate_group = Rdf::load($duplicate_id);
      if (empty($duplicate_group)) {
        continue;
      }

      // Unset the relationship to any distribution so that they can properly
      // moved.
      $duplicate_group->set('field_is_distribution', NULL);
      $duplicate_group->skip_notification = TRUE;
      $duplicate_group->save();

      $content_ids = $membership_manager->getGroupContentIds($duplicate_group);
      foreach ($content_ids as $entity_type_id => $entity_ids) {
        // Avoid processing other group content like og menu instances.
        if (!in_array($entity_type_id, ['node', 'rdf_entity'])) {
          continue;
        }
        $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
        foreach ($storage->loadMultiple($entity_ids) as $entity) {
          $entity->set(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $original_id);
          $entity->skip_notification = TRUE;
          $entity->save();
        }
      }
    }
  }
}

/**
 * Remove ctt duplicates.
 */
function _joinup_core_post_update_ctt_duplicates_delete_duplicates() {
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicated_ids) {
    foreach (Rdf::loadMultiple($duplicated_ids) as $entity) {
      $entity->skip_notification = TRUE;
      $entity->delete();
    }
  }
}

/**
 * Update the affiliates of the ctt collection.
 */
function _joinup_core_post_update_ctt_duplicates_collection_relations() {
  // The id of the ctt collection.
  $id = 'http://administracionelectronica.gob.es/ctt';

  $collection = Rdf::load($id);
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicate_ids) {
    $collection->field_ar_affiliates->appendItem($original_id);
  }
  $collection->skip_notification = TRUE;
  $collection->save();

  $connection = \Drupal::service('sparql_endpoint');
  // Update all other references.
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicate_ids) {
    $ids = SparqlArg::serializeUris($duplicate_ids, ' ');
    if (!empty($ids)) {
      $query = <<<QUERY
        INSERT { GRAPH ?g { ?subject ?predicate <$original_id> } }
        WHERE {
          GRAPH ?g {
            ?subject ?predicate ?object .
            VALUES ?object { $ids } .
            FILTER NOT EXISTS {
              ?subject ?predicate <$original_id> .
            }
          }
        }
QUERY;
      $connection->query($query);

      $query = <<<QUERY
        DELETE { GRAPH ?g { ?subject ?predicate ?object } }
        WHERE {
          GRAPH ?g {
            ?subject ?predicate ?object .
            VALUES ?object { $ids } .
          }
        }
QUERY;
      $connection->query($query);
    }
  }
}

/**
 * Retrieves a list of entities with their duplicated ids.
 *
 * @return array
 *   An associative array of ids where each entry is an array of ids of
 *   duplicates.
 */
function _joinup_core_get_duplicated_ids(): array {
  return [
    'http://administracionelectronica.gob.es/ctt/dscp' => [
      'http://administracionelectronica.gob.es/ctt/dscp_1',
    ],
    'http://administracionelectronica.gob.es/ctt/pau' => [
      'http://administracionelectronica.gob.es/ctt/pau_1',
    ],
    'http://administracionelectronica.gob.es/ctt/pfiaragon' => [
      'http://administracionelectronica.gob.es/ctt/pfiaragon_1',
    ],
    'http://administracionelectronica.gob.es/ctt/dir3' => [
      'http://administracionelectronica.gob.es/ctt/dir3_1',
    ],
    'http://administracionelectronica.gob.es/ctt/svd' => [
      'http://administracionelectronica.gob.es/ctt/svd_1',
    ],
    'http://administracionelectronica.gob.es/ctt/scsp' => [
      'http://administracionelectronica.gob.es/ctt/scsp_1',
    ],
    'http://administracionelectronica.gob.es/ctt/tsa' => [
      'http://administracionelectronica.gob.es/ctt/tsa_1',
    ],
    'http://administracionelectronica.gob.es/ctt/afirma' => [
      'http://administracionelectronica.gob.es/ctt/afirma_1',
      'http://administracionelectronica.gob.es/ctt/afirma_2',
    ],
    'http://administracionelectronica.gob.es/ctt/codice' => [
      'http://administracionelectronica.gob.es/ctt/codice_1',
    ],
    'http://administracionelectronica.gob.es/ctt/badaral' => [
      'http://administracionelectronica.gob.es/ctt/badaral_1',
    ],
    'http://administracionelectronica.gob.es/ctt/sicres' => [
      'http://administracionelectronica.gob.es/ctt/sicres_1',
    ],
    'http://administracionelectronica.gob.es/ctt/pruebalola' => [
      'http://administracionelectronica.gob.es/ctt/pruebalola_1',
    ],
    'http://administracionelectronica.gob.es/ctt/alpadron' => [
      'http://administracionelectronica.gob.es/ctt/alpadron_1',
    ],
    'http://administracionelectronica.gob.es/ctt/avanzalocalgis' => [
      'http://administracionelectronica.gob.es/ctt/avanzalocalgis_1',
    ],
  ];
}
