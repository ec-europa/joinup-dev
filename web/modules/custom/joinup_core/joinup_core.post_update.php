<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

use Drupal\rdf_entity\Entity\RdfEntityMapping;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
use EasyRdf\Resource;

/**
 * Enable the Sub-Pathauto module.
 */
function joinup_core_post_update_enable_subpathauto() {
  \Drupal::service('module_installer')->install(['subpathauto']);
}

/**
 * Enable the Views Bulk Operations module.
 */
function joinup_core_post_update_install_vbo() {
  \Drupal::service('module_installer')->install(['views_bulk_operations']);
}

/**
 * Enable the Email Registration module.
 */
function joinup_core_post_update_install_email_registration() {
  \Drupal::service('module_installer')->install(['email_registration']);
}

/**
 * Enable the Joinup Invite module.
 */
function joinup_core_post_update_install_joinup_invite() {
  \Drupal::service('module_installer')->install(['joinup_invite']);
}

/**
 * Move the contact form attachments under the private scheme.
 */
function joinup_core_post_update_move_contact_form_attachments() {
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = Drupal::service('file_system');

  $message_storage = \Drupal::entityTypeManager()->getStorage('message');
  $ids = $message_storage->getQuery()
    ->condition('template', 'contact_form_submission')
    ->exists('field_contact_attachment')
    ->execute();

  foreach ($message_storage->loadMultiple($ids) as $message) {
    /** @var \Drupal\file\FileInterface $attachment */
    if ($attachment = $message->field_contact_attachment->entity) {
      if (!file_exists($attachment->getFileUri())) {
        continue;
      }
      $target = file_uri_target($attachment->getFileUri());
      $uri = "private://$target";
      $destination_dir = $file_system->dirname($uri);
      if (!file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY)) {
        throw new \RuntimeException("Cannot create directory '$destination_dir'.");
      }
      if (!file_move($attachment, $uri)) {
        throw new \RuntimeException("Cannot move '{$attachment->getFileUri()}' to '$uri'.");
      }
    }
  }

  // Finally, remove the empty public://contact_form directory.
  file_unmanaged_delete_recursive('public://contact_form');
}

/**
 * Enable the Smart Trim module.
 */
function joinup_core_post_update_install_smart_trim() {
  \Drupal::service('module_installer')->install(['smart_trim']);
}

/**
 * Remove stale 'system.action.joinup_transfer_solution_ownership' config.
 */
function joinup_core_post_update_remove_action_transfer_solution_ownership() {
  \Drupal::configFactory()
    ->getEditable('system.action.joinup_transfer_solution_ownership')
    ->delete();
}

/**
 * Enable 'spain_ctt' module.
 */
function joinup_core_post_update_install_spain_ctt() {
  \Drupal::service('module_installer')->install(['spain_ctt']);
}

/**
 * Enable and configure the 'rdf_schema_field_validation' module.
 */
function joinup_core_post_update_configure_rdf_schema_field_validation() {
  \Drupal::service('module_installer')->install(['rdf_schema_field_validation']);
  $graph_uri = 'http://adms-definition';
  $class_definition = 'http://www.w3.org/2000/01/rdf-schema#Class';

  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $connection_options = $sparql_endpoint->getConnectionOptions();
  $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
  // Use a local SPARQL 1.1 Graph Store.
  $gs = new GraphStore($connect_string);
  $graph = new Graph($graph_uri);
  $graph->parseFile(DRUPAL_ROOT . '/../resources/fixtures/adms-definition.rdf');
  $gs->replace($graph);

  $data = ['collection', 'solution', 'asset_release', 'asset_distribution'];
  foreach ($data as $bundle) {
    RdfEntityMapping::loadByName('rdf_entity', $bundle)
      ->setThirdPartySetting('rdf_schema_field_validation', 'property_predicates', ['http://www.w3.org/2000/01/rdf-schema#domain'])
      ->setThirdPartySetting('rdf_schema_field_validation', 'graph', $graph_uri)
      ->setThirdPartySetting('rdf_schema_field_validation', 'class', $class_definition)
      ->save();
  }
}

/**
 * Fix the banner predicate [ISAICP-4332].
 */
function joinup_core_post_update_fix_banner_predicate() {
  /** @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $retrieve_query = <<<QUERY
  SELECT ?graph ?entity_id ?image_uri
  FROM <http://joinup.eu/collection/published>
  FROM <http://joinup.eu/collection/draft>
  FROM <http://joinup.eu/solution/published>
  FROM <http://joinup.eu/solution/draft>
  FROM <http://joinup.eu/asset_release/published>
  FROM <http://joinup.eu/asset_release/draft>
  WHERE {
    GRAPH ?graph {
      ?entity_id a ?type .
      ?entity_id <http://xmlns.com/foaf/0.1/#term_Image> ?image_uri
    }
  }
QUERY;

  $results = $sparql_endpoint->query($retrieve_query);
  $items_to_update = [];
  foreach ($results as $result) {
    // Index by entity id so that only one value is inserted.
    $items_to_update[$result->graph->getUri()][$result->entity_id->getUri()] = [
      'value' => $result->image_uri->getValue(),
      'datatype' => $result->image_uri->getDatatype(),
    ];
  }

  $update_query = <<<QUERY
  WITH <@graph>
  DELETE {
    <@entity_id> <http://xmlns.com/foaf/0.1/#term_Image> "@value"^^@datatype
  }
  INSERT {
    <@entity_id> <http://xmlns.com/foaf/0.1/Image> "@value"^^@datatype
  }
  WHERE {
    <@entity_id> <http://xmlns.com/foaf/0.1/#term_Image> "@value"^^@datatype
  }
QUERY;
  $search = ['@graph', '@entity_id', '@value', '@datatype'];
  foreach ($items_to_update as $graph => $graph_data) {
    foreach ($graph_data as $entity_id => $item) {
      $replace = [
        $graph,
        $entity_id,
        $item['value'],
        $item['datatype'],
      ];
      $query = str_replace($search, $replace, $update_query);
      $sparql_endpoint->query($query);
    }
  }
}

/**
 * Fix the owner class predicate [ISAICP-4333].
 */
function joinup_core_post_update_fix_owner_predicate() {
  $rdf_entity_mapping = RdfEntityMapping::loadByName('rdf_entity', 'owner');
  $rdf_entity_mapping->setRdfType('http://xmlns.com/foaf/0.1/Agent');
  $rdf_entity_mapping->save();

  /** @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $retrieve_query = <<<QUERY
SELECT ?graph ?entity_id ?type
WHERE {
  GRAPH ?graph {
    ?entity_id a ?type .
    VALUES ?graph { <http://joinup.eu/owner/published> <http://joinup.eu/owner/draft> }
  }
}
QUERY;

  $results = $sparql_endpoint->query($retrieve_query);
  $items_to_update = [];
  foreach ($results as $result) {
    // Index by entity id so that only one value is inserted.
    $items_to_update[$result->graph->getUri()][] = $result->entity_id->getUri();
  }

  $update_query = <<<QUERY
  WITH <@graph>
  DELETE {
    <@entity_id> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/spec/#term_Agent>
  }
  INSERT {
    <@entity_id> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Agent>
  }
  WHERE {
    <@entity_id> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/spec/#term_Agent>
  }
QUERY;
  $search = ['@graph', '@entity_id'];
  foreach ($items_to_update as $graph => $entity_ids) {
    foreach ($entity_ids as $entity_id) {
      $replace = [
        $graph,
        $entity_id,
      ];
      $query = str_replace($search, $replace, $update_query);
      $sparql_endpoint->query($query);
    }
  }
}

/**
 * Fix data type of the solution contact point[ISAICP-4334].
 */
function joinup_core_post_update_fix_solution_contact_datatypea() {
  /** @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  // Two issues are to be fixed here.
  // 1. The contact reference should be a resource instead of a literal.
  // 2. The predicate should be updated so that the IRI does not use the https
  // protocol.
  $retrieve_query = <<<QUERY
SELECT ?graph ?entity_id ?contact
WHERE {
  GRAPH ?graph {
    ?entity_id a ?type .
    ?entity_id <https://www.w3.org/ns/dcat#contactPoint> ?contact .
  }
}
QUERY;
  $results = $sparql_endpoint->query($retrieve_query);
  $items_to_update = [];
  foreach ($results as $result) {
    $contact_uri = $result->contact instanceof Resource ? $result->contact->getUri() : $result->contact->getValue();
    // Index by entity id so that only one value is inserted.
    $items_to_update[$result->graph->getUri()][$result->entity_id->getUri()][] = $contact_uri;
  }

  $update_query = <<<QUERY
  WITH <@graph>
  DELETE {
    <@entity_id> <https://www.w3.org/ns/dcat#contactPoint> ?contact_uri
  }
  INSERT {
    <@entity_id> <http://www.w3.org/ns/dcat#contactPoint> <@contact_uri>
  }
  WHERE {
    <@entity_id> <https://www.w3.org/ns/dcat#contactPoint> ?contact_uri .
    FILTER (str(?contact_uri) = str("@contact_uri"))
  }
QUERY;
  $search = ['@graph', '@entity_id', '@contact_uri'];
  foreach ($items_to_update as $graph => $graph_data) {
    foreach ($graph_data as $entity_id => $contact_uris) {
      foreach ($contact_uris as $contact_uri) {
        $replace = [
          $graph,
          $entity_id,
          $contact_uri,
        ];
        $query = str_replace($search, $replace, $update_query);
        $sparql_endpoint->query($query);
      }
    }
  }
}

/**
 * Fix data type of the access url field [ISAICP-4349].
 */
function joinup_core_post_update_fix_access_url_datatype() {
  /** @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $retrieve_query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?access_url
WHERE {
  GRAPH ?graph {
    ?entity_id a ?type .
    ?entity_id ?predicate ?access_url .
    VALUES ?type { <http://www.w3.org/ns/dcat#Catalog> <http://www.w3.org/ns/dcat#Distribution> }
    VALUES ?predicate { <http://www.w3.org/ns/dcat#accessURL> <http://xmlns.com/foaf/spec/#term_homepage> }
    VALUES ?graph { <http://joinup.eu/collection/draft> <http://joinup.eu/collection/published> <http://joinup.eu/asset_distribution/published> }
    FILTER (!datatype(?access_url) = xsd:anyURI) .
  }
}
QUERY;
  $results = $sparql_endpoint->query($retrieve_query);
  $items_to_update = [];
  foreach ($results as $result) {
    // The above query should not have errors or duplicated values so lets keep
    // a simple array structure.
    $items_to_update[] = [
      '@graph' => $result->graph->getUri(),
      '@entity_id' => $result->entity_id->getUri(),
      '@predicate' => $result->predicate->getUri(),
      '@access_url' => $result->access_url->getValue(),
    ];
  }

  $update_query = <<<QUERY
  WITH <@graph>
  DELETE {
    <@entity_id> <@predicate> "@access_url"^^<http://www.w3.org/2001/XMLSchema#string> .
    <@entity_id> <@predicate> "@access_url" .
  }
  INSERT {
    <@entity_id> <@predicate> "@access_url"^^<http://www.w3.org/2001/XMLSchema#anyURI>
  }
  WHERE {
    <@entity_id> <@predicate> ?access_url .
    VALUES ?access_url { "@access_url"^^<http://www.w3.org/2001/XMLSchema#string> "@access_url" }
  }
QUERY;
  foreach ($items_to_update as $data_array) {
    $query = str_replace(array_keys($data_array), array_values($data_array), $update_query);
    $sparql_endpoint->query($query);
  }
}

/**
 * Enable the Tallinn module.
 */
function joinup_core_post_update_install_tallinn() {
  \Drupal::service('module_installer')->install(['tallinn']);
}

/**
 * Migrate from Piwik to Matomo.
 */
function joinup_core_post_update_install_piwik2matomo() {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $installer */
  $installer = \Drupal::service('module_installer');
  // Install the new modules. This is also uninstalling 'piwik_reporting_api'.
  $installer->install(['matomo_reporting_api']);
  // Uninstall the Piwik module.
  $installer->uninstall(['piwik']);
  // Note that the module installer API requires the presence of the modules in
  // the codebase. For this reason they will be removed from the codebase in a
  // follow-up.
}

/**
 * Enable the 'joinup_sparql' and 'joinup_federation' modules.
 */
function joinup_core_post_update_install_modules() {
  \Drupal::service('module_installer')->install(['joinup_sparql', 'joinup_federation']);
}

/**
 * Add the user support menu.
 */
function joinup_core_post_update_remove_tour_buttons() {
  \Drupal::service('module_installer')->install(['menu_admin_per_menu']);
  $config_factory = \Drupal::configFactory();
  $config_factory->getEditable('block.block.tourbutton_2')->delete();
  $config_factory->getEditable('block.block.tourbutton')->delete();
}

/**
 * Enable the 'error_page' module.
 */
function joinup_core_post_update_install_error_page() {
  \Drupal::service('module_installer')->install(['error_page']);
}
