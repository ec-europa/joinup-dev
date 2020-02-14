<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\redirect\Entity\Redirect;
use Drupal\search_api\Entity\Index;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
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
    SparqlMapping::loadByName('rdf_entity', $bundle)
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
  /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
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
  $sparql_mapping = SparqlMapping::loadByName('rdf_entity', 'owner');
  $sparql_mapping->setRdfType('http://xmlns.com/foaf/0.1/Agent');
  $sparql_mapping->save();

  /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
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
  /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
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
  /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
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

/**
 * Update the EIRA terms and perform other related tasks.
 */
function joinup_core_post_update_eira() {
  \Drupal::service('module_installer')->install(['eira']);

  $graph_uri = 'http://eira_skos';
  /** @var \Drupal\Driver\Database\joinup_sparql\Connection $connection */
  $connection = \Drupal::service('sparql_endpoint');
  $connection->query('CLEAR GRAPH <http://eira_skos>;');

  $graph = new Graph($graph_uri);
  $filename = DRUPAL_ROOT . '/../resources/fixtures/EIRA_SKOS.rdf';
  $graph->parseFile($filename);

  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);
  $graph_store->insert($graph);

  $graphs = [
    'http://joinup.eu/solution/published',
    'http://joinup.eu/solution/draft',
  ];
  $map = [
    'http://data.europa.eu/dr8/ConfigurationAndCartographyService' => 'http://data.europa.eu/dr8/ConfigurationAndSolutionCartographyService',
    'http://data.europa.eu/dr8/TestReport' => 'http://data.europa.eu/dr8/ConformanceTestReport',
    'http://data.europa.eu/dr8/TestService' => 'http://data.europa.eu/dr8/ConformanceTestingService',
    'http://data.europa.eu/dr8/ConfigurationAndCartographyServiceComponent' => 'http://data.europa.eu/dr8/ConfigurationAndSolutionCartographyServiceComponent',
    'http://data.europa.eu/dr8/TestComponent' => 'http://data.europa.eu/dr8/ConformanceTestingComponent',
    'http://data.europa.eu/dr8/ApplicationService' => 'http://data.europa.eu/dr8/InteroperableEuropeanSolutionService',
    'http://data.europa.eu/dr8/TestScenario' => 'http://data.europa.eu/dr8/ConformanceTestScenario',
    'http://data.europa.eu/dr8/PublicPolicyDevelopmentMandate' => 'http://data.europa.eu/dr8/PublicPolicyImplementationMandate',
    'http://data.europa.eu/dr8/Data-levelMapping' => 'http://data.europa.eu/dr8/DataLevelMapping',
    'http://data.europa.eu/dr8/Schema-levelMapping' => 'http://data.europa.eu/dr8/SchemaLevelMapping',
    'http://data.europa.eu/dr8/AuditAndLoggingComponent' => 'http://data.europa.eu/dr8/AuditComponent',
    'http://data.europa.eu/dr8/LegalRequirementOrConstraint' => 'http://data.europa.eu/dr8/LegalAct',
    'http://data.europa.eu/dr8/BusinessInformationExchange' => 'http://data.europa.eu/dr8/ExchangeOfBusinessInformation',
    'http://data.europa.eu/dr8/InteroperabilityAgreement' => 'http://data.europa.eu/dr8/OrganisationalInteroperabilityAgreement',
    'http://data.europa.eu/dr8/BusinessProcessManagementComponent' => 'http://data.europa.eu/dr8/OrchestrationComponent',
    'http://data.europa.eu/dr8/HostingAndNetworkingInfrastructureService' => 'http://data.europa.eu/dr8/HostingAndNetworkingInfrastructure',
    'http://data.europa.eu/dr8/PublicPolicyDevelopmentApproach' => 'http://data.europa.eu/dr8/PublicPolicyImplementationApproach',
  ];

  foreach ($graphs as $graph) {
    foreach ($map as $old_uri => $new_uri) {
      $query = <<<QUERY
WITH <$graph>
DELETE { ?solution_id <http://purl.org/dc/terms/type> <$old_uri> }
INSERT { ?solution_id <http://purl.org/dc/terms/type> <$new_uri> }
WHERE { ?solution_id <http://purl.org/dc/terms/type> <$old_uri> }
QUERY;
      $connection->query($query);
    }
  }

  // Finally, repeat the process that initially fixed the eira skos vocabulary.
  // @see ISAICP-3216.
  // @see \DrupalProject\Phing\AfterFixturesImportCleanup::main()
  //
  // Add the "Concept" type to all collection elements so that they are listed
  // as Parent terms.
  $connection->query('INSERT INTO <http://eira_skos> { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
  // Add the link to all "Concept" type elements so that they are all considered
  // as children of the EIRA vocabulary regardless of the depth.
  $connection->query('INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { GRAPH <http://eira_skos> { ?subject a skos:Concept .} };');
  // Create a backwards connection from the children to the parent.
  $connection->query('INSERT INTO <http://eira_skos> { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');
}

/**
 * Remove temporary 'file' entities that lack the file on file system.
 */
function joinup_core_post_update_fix_files(array &$sandbox) {
  if (!isset($sandbox['fids'])) {
    $sandbox['fids'] = array_values(\Drupal::entityQuery('file')
      ->condition('status', FILE_STATUS_PERMANENT, '<>')
      ->sort('fid')
      ->execute());
    $sandbox['processed'] = 0;
  }

  $fids = array_splice($sandbox['fids'], 0, 50);
  foreach (File::loadMultiple($fids) as $file) {
    /** @var \Drupal\file\FileInterface $file */
    if (!file_exists($file->getFileUri())) {
      $file->delete();
      $sandbox['processed']++;
    }
  }

  $sandbox['#finished'] = (int) !$sandbox['fids'];

  if ($sandbox['#finished'] === 1) {
    return $sandbox['processed'] ? "{$sandbox['processed']} file entities deleted." : "No file entities were deleted.";
  }
}

/**
 * Force-update all distribution aliases.
 */
function joinup_core_post_update_create_distribution_aliases(array &$sandbox) {
  if (!isset($sandbox['entity_ids'])) {
    // In order to force-update all distribution aliases in a post_update
    // function the pattern config file is imported manually, as normally, the
    // config sync runs after the database updatess.
    $pathauto_settings = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/profiles/joinup/config/install/pathauto.pattern.rdf_entities_distributions.yml'));
    \Drupal::configFactory()
      ->getEditable('pathauto.pattern.rdf_entities_distributions')
      ->setData($pathauto_settings)
      ->save();

    $sandbox['entity_ids'] = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'asset_distribution')
      ->execute();
    $sandbox['current'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
  $pathauto_generator = \Drupal::service('pathauto.generator');

  $result = array_slice($sandbox['entity_ids'], $sandbox['current'], 50);
  foreach ($entity_storage->loadMultiple($result) as $entity) {
    $pathauto_generator->updateEntityAlias($entity, 'update', ['force' => TRUE]);
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
  return "Processed {$sandbox['current']} out of {$sandbox['max']}.";
}

/**
 * Create release aliases and create a redirect from the existing ones.
 */
function joinup_core_post_update_create_new_release_aliases(array &$sandbox): string {
  if (!isset($sandbox['entity_ids'])) {
    $pathauto_settings = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/profiles/joinup/config/install/pathauto.pattern.rdf_entities_releases.yml'));
    \Drupal::configFactory()
      ->getEditable('pathauto.pattern.rdf_entities_releases')
      ->setData($pathauto_settings)
      ->save();

    $sandbox['entity_ids'] = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'asset_release')
      ->execute();
    $sandbox['current'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
  $pathauto_generator = \Drupal::service('pathauto.generator');

  $result = array_slice($sandbox['entity_ids'], $sandbox['current'], 50);
  foreach ($entity_storage->loadMultiple($result) as $entity) {
    $source_url = $entity->toUrl()->toString();
    $new_alias = $pathauto_generator->createEntityAlias($entity, 'insert');
    Redirect::create([
      'redirect_source' => $source_url,
      'redirect_redirect' => 'internal:' . $new_alias['alias'],
      'language' => 'und',
      'status_code' => '301',
    ])->save();
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
  return "Processed {$sandbox['current']} out of {$sandbox['max']}.";
}

/**
 * Create news aliases for news, event, discussion and document content types.
 */
function joinup_core_post_update_create_new_node_aliases(array &$sandbox): string {
  if (!isset($sandbox['entity_ids'])) {
    $pathauto_settings = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/profiles/joinup/config/install/pathauto.pattern.community_content.yml'));
    \Drupal::configFactory()
      ->getEditable('pathauto.pattern.community_content')
      ->setData($pathauto_settings)
      ->save();

    $bundles = ['news', 'event', 'discussion', 'document'];
    $sandbox['entity_ids'] = \Drupal::entityQuery('node')
      ->condition('type', $bundles, 'IN')
      ->execute();
    $sandbox['current'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_storage = \Drupal::entityTypeManager()->getStorage('node');
  /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
  $pathauto_generator = \Drupal::service('pathauto.generator');

  $result = array_slice($sandbox['entity_ids'], $sandbox['current'], 50);
  foreach ($entity_storage->loadMultiple($result) as $entity) {
    $source_url = $entity->toUrl()->toString();
    $new_alias = $pathauto_generator->createEntityAlias($entity, 'insert');

    Redirect::create([
      'redirect_source' => $source_url,
      'redirect_redirect' => 'internal:' . $new_alias['alias'],
      'language' => 'und',
      'status_code' => '301',
    ])->save();
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
  return "Processed {$sandbox['current']} out of {$sandbox['max']}.";
}

/**
 * Disable database logging, use the syslog instead.
 */
function joinup_core_post_update_swap_dblog_with_syslog() {
  // Writing log entries in the database during anonymous requests is causing
  // load on the database. Another problem is that there is a cap on the number
  // of log entries that are retained in the the database. On some occasions
  // during heavy logging activity they rotated before we had the chance to read
  // them. Write the log entries to the syslog instead.
  \Drupal::service('module_installer')->install(['syslog']);
  \Drupal::service('module_installer')->uninstall(['dblog']);
}

/**
 * Enable the spdx module.
 */
function joinup_core_post_update_enable_spdx() {
  \Drupal::service('module_installer')->install(['spdx']);
}

/**
 * Re import the legal type vocabulary so that the weight is imported.
 */
function joinup_core_post_update_re_import_legal_type_vocabulary() {
  \Drupal::service('joinup_core.vocabulary_fixtures.helper')->importFixtures('licence-legal-type');
}

/**
 * Corrects the versions of faulty news items.
 */
function joinup_core_post_update_set_news_default_version() {
  // Due to some cache state inconsistency, some nodes had their state
  // reverted in a previous version without creating a new revision for this.
  // While in a Drupal site it is normal to have forward revisions, it is not
  // normal to have forward published revisions. If the entity is published,
  // then the default version(current published) should be the latest
  // revision. Instead, what happens is that these entities are published but
  // also have revision(s) that are also published but of a newer version id.
  //
  // The query used is only returning published revisions as even if there is a
  // forward draft revision in the entity, the draft versions are not published
  // and thus, are not the default versions. This will set the latest published
  // revision as the default one.
  $results = \Drupal::service('joinup_core.requirements_helper')->getNodesWithProblematicRevisions();
  $nids = array_keys($results);
  /** @var \Drupal\node\NodeStorage $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  /** @var \Drupal\node\NodeInterface $node */
  foreach ($node_storage->loadMultiple($nids) as $node) {
    $latest_revision = $node_storage->loadRevision($results[$node->id()]->latest_vid);
    $latest_revision->isDefaultRevision(TRUE);
    $latest_revision->save();
  }
}

/**
 * Enable the nio module.
 */
function joinup_core_post_update_enable_nio() {
  \Drupal::service('module_installer')->install(['nio']);
}

/**
 * Enable the Publication Date module.
 */
function joinup_core_post_update_install_publication_date() {
  \Drupal::service('module_installer')->install(['publication_date']);
}

/**
 * Enable the joinup_privacy module.
 */
function joinup_core_post_update_enable_joinup_privacy() {
  \Drupal::service('module_installer')->install(['joinup_privacy']);
}

/**
 * Deletes unused files explicitly requested for deletion.
 */
function joinup_core_post_update_delete_orphaned_files() {
  $files_to_remove = [
    'public://document/2017-05/e-trustex_software_architecture_document_0.pdf',
    'public://document/2013-12/e-TrustEx Interface Control Document.pdf',
  ];

  $file_storage = \Drupal::entityTypeManager()->getStorage('file');
  foreach ($files_to_remove as $uri) {
    $files = $file_storage->loadByProperties(['uri' => $uri]);
    if ($file = reset($files)) {
      $file->delete();
    }
  }
}

/**
 * Reset the publication dates.
 */
function joinup_core_post_update_0_fix_publication_dates() {
  // Due to an incorrect earlier version of the install hook of the
  // Publication Date module a number of older news items were present without a
  // publication date. Erase all publication dates and restore them.
  // Note that since this update hook is prefixed with a 0 it is guaranteed to
  // run before the post update hook of the Publication Date module.
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $connection = \Drupal::database();
  $connection->update($node_storage->getDataTable())
    ->fields(['published_at' => NULL])
    ->execute();
  $connection->update($node_storage->getRevisionDataTable())
    ->fields(['published_at' => NULL])
    ->execute();
}

/**
 * Reset the publication dates again.
 */
function joinup_core_post_update_refix_publication_dates() {
  $connection = Database::getConnection();

  // Clean up the values from the database and start anew.
  joinup_core_post_update_0_fix_publication_dates();

  // The upstream update path is using the `changed` timestamp in order to
  // update the publication timestamp. However, we already have a way of
  // accurately tracking it, the `created` property. We already update the
  // `created` property during the initial publication so by reproducing the
  // upstream queries but having the `created` property used instead, we
  // properly set the publication date.
  $queries = [
    // Update nodes with multiple revisions that have at least one published
    // revision so the publication date is set to the created timestamp of the
    // first published revision.
    [
      'query' => <<<SQL
UPDATE {node_field_revision} r, (
  SELECT
    nid,
    MIN(vid) as vid,
    MIN(created) as created
  FROM {node_field_revision}
  WHERE status = 1
  GROUP BY nid
  ORDER BY vid
) s
SET r.published_at = s.created
WHERE r.nid = s.nid AND r.vid >= s.vid;
SQL
      ,
      'arguments' => [],
    ],

    // Set the remainder of the publication dates in the revisions table to the
    // default timestamp. This applies to all revisions that were created before
    // the node was first published.
    [
      'query' => <<<SQL
UPDATE {node_field_revision} r
SET r.published_at = :default_timestamp
WHERE r.published_at IS NULL;
SQL
      ,
      'arguments' => [':default_timestamp' => PUBLICATION_DATE_DEFAULT],
    ],

    // Copy the publication date from the revisions table to the node table.
    [
      'query' => <<<SQL
UPDATE {node_field_data} d, {node_field_revision} r
SET d.published_at = r.published_at
WHERE d.vid = r.vid;
SQL
      ,
      'arguments' => [],
    ],
  ];

  // Perform the operations in a single atomic transaction.
  $transaction = $connection->startTransaction();
  try {
    foreach ($queries as $query_data) {
      \Drupal::database()->query($query_data['query'], $query_data['arguments']);
    }
  }
  catch (Exception $e) {
    $transaction->rollBack();
    throw new Exception('Database error', 0, $e);
  }
}

/**
 * Stats #3: Repair Search API task.
 */
function joinup_core_post_update_stats3(): void {
  $db = \Drupal::database();

  $tasks = $db->select('search_api_task')
    ->fields('search_api_task', ['id', 'data'])
    ->condition('type', 'updateIndex')
    ->condition('server_id', 'solr_published')
    ->condition('index_id', 'published')
    ->execute()
    ->fetchAllKeyed();

  $published_index_values = Index::load('published')->toArray();
  foreach ($tasks as $id => $data) {
    $data = unserialize($data);
    // When a Search API index config entity is updated, a reindex is triggered
    // but, for some reasons, the task uses the 'original' index config entity
    // version.
    // @see \Drupal\search_api\Entity\Server::updateIndex()
    $data['#values'] = $published_index_values;
    $db->update('search_api_task')
      ->condition('id', $id)
      ->fields(['data' => serialize($data)])
      ->execute();
  }
}

/**
 * Stats #4: Create metadata entities.
 */
function joinup_core_post_update_stats4(array &$sandbox): ?string {
  $db = \Drupal::database();
  if (!isset($sandbox['current'])) {
    $sandbox['current'] = 0;
    $sandbox['processed'] = ['rdf_entity' => 0, 'node' => 0];
  }

  $items = $db->select('joinup_core_stats_update_temp')
    ->fields('joinup_core_stats_update_temp')
    ->condition('id', $sandbox['current'], '>')
    ->orderBy('id')
    ->range(0, 500)
    ->execute()
    ->fetchAll();
  $sandbox['#finished'] = (int) empty($items);

  if (!$sandbox['#finished']) {
    /** @var \Drupal\Component\Uuid\UuidInterface $uuid */
    $uuid = \Drupal::service('uuid');
    $timestamp = \Drupal::time()->getRequestTime();

    foreach ($items as $item) {
      $target_entity_type_id = $item->entity_type_id;
      $target_entity_id = $item->entity_id;
      $meta_entity_type_id = $target_entity_type_id === 'rdf_entity' ? 'download_count' : 'visit_count';

      // We're using direct SQL insert statements to speedup the process.
      $meta_entity_id = $db->insert('meta_entity')->fields([
        'type' => $meta_entity_type_id,
        'uuid' => $uuid->generate(),
        'label' => "$target_entity_type_id: $target_entity_id",
        'target__target_id' => $target_entity_id,
        'target__target_type' => $target_entity_type_id,
        'created' => $timestamp,
        'changed' => $timestamp,
        'target__target_id_int' => $target_entity_type_id === 'rdf_entity' ? NULL : (int) $target_entity_id,
      ])->execute();
      $db->insert('meta_entity__count')->fields([
        'bundle' => $meta_entity_type_id,
        'entity_id' => $meta_entity_id,
        'revision_id' => $meta_entity_id,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'delta' => 0,
        'count_value' => (int) $item->counter,
      ])->execute();
      $sandbox['current'] = $item->id;
      $sandbox['processed'][$target_entity_type_id]++;
    }

    return "Progress: {$sandbox['processed']['rdf_entity']} distributions, {$sandbox['processed']['node']} nodes.";
  }
  $db->schema()->dropTable('joinup_core_stats_update_temp');

  return "Finished processing {$sandbox['processed']['rdf_entity']} distributions and {$sandbox['processed']['node']} nodes.";
}

/**
 * Stats #5: Remove stale triples.
 */
function joinup_core_post_update_stats5(): void {
  /** @var \Drupal\Driver\Database\sparql\Connection $sparql_connection */
  $sparql_connection = \Drupal::service('sparql.endpoint');
  $sparql_connection->query("WITH <http://joinup.eu/asset_distribution/published>
DELETE {
  ?s ?p ?o .
}
WHERE {
  ?s ?p ?o .
  VALUES ?p { <http://schema.org/userInteractionCount> <http://schema.org/expires> }
}");
}

/**
 * Stats #6: Update field queue.
 */
function joinup_core_post_update_stats6(array &$sandbox): ?string {
  $db = \Drupal::database();

  if (!isset($sandbox['items'])) {
    // Make sure that cron is not consuming the queue while we're updating it.
    // We try to acquire a lock, pretending that cron is running. If we succeed,
    // a cron that attempts to start after this update has started, will not run
    // as it will not be able to acquire the lock. If we fail, means the cron
    // has already started before this update. That should be reported, as all
    // cron processes should be stopped before starting the Joinup update.
    // @see \Drupal\Core\Cron::run()
    if (!\Drupal::lock()->acquire('cron', 900.0)) {
      // Cron is currently running.
      throw new \RuntimeException("Cannot update Joinup because cron is currently running. Ensure the conjob processes are stopped before attempting to update Joinup.");
    }
    $sandbox['items'] = [];
    $sandbox['processed'] = 0;

    // Store all 'cached_computed_field_expired_fields' queue items in sandbox.
    $items = $db->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('name', 'cached_computed_field_expired_fields')
      ->orderBy('q.item_id')
      ->execute()
      ->fetchAllKeyed();
    foreach ($items as $item_id => $data) {
      $data = unserialize($data);
      $sandbox['items'][] = [
        'id' => $item_id,
        'entity_id' => $data['entity_id'],
        'entity_type_id' => $data['entity_type'],
      ];
    }
  }

  if ($items_to_process = array_splice($sandbox['items'], 0, 500)) {
    $ids = array_map(function (array $item): string {
      return $item['entity_id'];
    }, $items_to_process);
    $entities = $db->select('meta_entity', 'm')
      ->fields('m', ['target__target_id', 'id'])
      ->condition('target__target_id', $ids, 'IN')
      ->execute()
      ->fetchAllKeyed();
    $items_to_delete = [];
    foreach ($items_to_process as $item) {
      if (isset($entities[$item['entity_id']])) {
        // References to content entities replaced with same to meta entities.
        $data = [
          'entity_type' => 'meta_entity',
          'entity_id' => $entities[$item['entity_id']],
          'field_name' => 'count',
          'expire' => 0,
        ];
        $db->update('queue')
          ->fields(['data' => serialize($data)])
          ->condition('item_id', $item['id'])
          ->execute();
      }
      else {
        // The entity might have been deleted in the meantime.
        $items_to_delete[] = $item['id'];
      }
      if ($items_to_delete) {
        $db->delete('queue')
          ->condition('item_id', $items_to_delete, 'IN')
          ->execute();
      }
      $sandbox['processed']++;
    }
  }

  $sandbox['#finished'] = $sandbox['items'] ? 0 : 1;

  if ($sandbox['#finished']) {
    // Release the fake 'cron' lock.
    \Drupal::lock()->release('cron');
    return "Processed {$sandbox['processed']} items from queue.";
  }

  return "Finished processing {$sandbox['processed']} items from queue.";
}

/**
 * Correct the faulty revisions after the storage changes on the counters.
 */
function joinup_core_post_update_post_count_storage_node_revisions() {
  joinup_core_post_update_set_news_default_version();
}

/**
 * Clean up the migration tables.
 */
function joinup_core_post_update_remove_mdigrate_tables(array &$sandbox): string {
  $connection = Database::getConnection();
  $tables = $connection->query("SHOW TABLES LIKE 'migrate_%'")->fetchCol();
  $schema = $connection->schema();
  foreach ($tables as $table) {
    $schema->dropTable($table);
  }
  return 'Deleted tables: ' . implode(', ', $tables) . '.';
}
