<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

use Laminas\Diactoros\Uri;

/**
 * Update fields in specific solutions.
 */
function joinup_core_deploy_0107200(array &$sandbox): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  if (empty($sandbox['items'])) {
    // IDs and values for the owner types can be found with the following query
    // in the SPARQL endpoint:
    // @code
    // SELECT ?id ?value
    // FROM <http://adms_skos_v1.00>
    // WHERE {
    //  ?id <http://www.w3.org/2004/02/skos/core#prefLabel> ?value
    // }
    // @endcode
    // Same, for spatial coverage terms:
    // @code
    // SELECT ?id ?value
    // FROM <http://countries-skos>
    // WHERE {
    //  ?id <http://www.w3.org/2004/02/skos/core#prefLabel> ?value
    // }
    // @endcode
    // Also, predicate for solution spatial: <http://purl.org/dc/terms/spatial>.
    $sandbox['parent_data'] = [
      'http://www.juntadeandalucia.es/repositorio/' => [
        'field_is_contact_information' => [
          'rid' => 'contact_information',
          'field_ci_name' => 'La Junta de Andalucia',
          'field_ci_email' => 'dgtransformaciondigital.chie@juntadeandalucia.es',
          'field_ci_webpage' => 'http://www.juntadeandalucia.es/repositorio/',
        ],
        'field_is_owner' => [
          'rid' => 'owner',
          'field_owner_name' => 'La Junta de Andalucia',
          'field_owner_type' => [
            'http://purl.org/adms/publishertype/RegionalAuthority',
          ],
        ],
        'field_spatial_coverage' => 'http://publications.europa.eu/resource/authority/country/ESP',
      ],
      'http://www.unece.org/cefact/' => [
        'field_is_contact_information' => [
          'rid' => 'contact_information',
          'field_ci_name' => 'UN/CEFACT',
          'field_ci_email' => 'unece_info@un.org',
          'field_ci_webpage' => 'https://unece.org/',
        ],
      ],
    ];
    $sandbox['max'] = 0;
    foreach (array_keys($sandbox['parent_data']) as $parent_id) {
      $sandbox['items'][$parent_id] = array_map(function (array $value): string {
        return $value['target_id'];
      }, $rdf_storage->load($parent_id)->get('field_ar_affiliates')->getValue());
      $sandbox['max'] += count($sandbox['items'][$parent_id]);
    }
    $sandbox['progress'] = 0;
  }

  $parent_ids = array_keys($sandbox['items']);
  $parent_id = reset($parent_ids);
  $items = array_splice($sandbox['items'][$parent_id], 0, 50);
  if (empty($sandbox['items'][$parent_id])) {
    unset($sandbox['items'][$parent_id]);
  }

  /** @var \Drupal\solution\Entity\SolutionInterface $solution */
  foreach ($rdf_storage->loadMultiple($items) as $solution) {
    foreach ($sandbox['parent_data'][$parent_id] as $field_name => $value_data) {
      if (is_array($value_data)) {
        $new_entity = $rdf_storage->create($value_data);
        $new_entity->save();
        $value_data = $new_entity->id();
      }
      $solution->set($field_name, $value_data);
    }
    $solution->skip_notification = TRUE;
    $solution->save();
  }

  $sandbox['progress'] += count($items);
  $sandbox['#finished'] = ($sandbox['progress'] >= $sandbox['max']) ? 1 : (float) $sandbox['progress'] / (float) $sandbox['max'];
  return "Processed {$sandbox['progress']} out of {$sandbox['max']} items.";
}

/**
 * Clean orphan Solr document entries.
 */
function joinup_core_deploy_0107201(array &$sandbox = NULL): void {
  $http_client = \Drupal::httpClient();
  foreach (['published', 'unpublished'] as $index_id) {
    $endpoint = \Drupal::config("search_api.server.solr_{$index_id}")
      ->get('backend_config.connector_config');
    $uri = (string) (new Uri())
      ->withScheme($endpoint['scheme'])
      ->withHost($endpoint['host'])
      ->withPort($endpoint['port'])
      ->withPath(rtrim($endpoint['path'], '/') . "/{$endpoint['core']}/update")
      ->withQuery(http_build_query([
        'stream.body' => '<delete><query>-hash:tb6juv</query></delete>',
        'commit' => 'true',
        'wt' => 'json',
       ]));
    $http_client->get($uri);
  }
}
