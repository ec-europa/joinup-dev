<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;

/**
 * Implements helper methods related to the requirements.
 */
class RequirementsHelper {

  use StringTranslationTrait;

  /**
   * The SQL connection class for the SPARQL database storage.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * Constructs a new RequirementsHelper.
   *
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SQL connection class for the SPARQL database storage.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager service.
   */
  public function __construct(ConnectionInterface $sparql, TranslationManager $translation) {
    $this->sparql = $sparql;
    $this->translation = $translation;
  }

  /**
   * Returns a list of error messages for the joinup_rdf requirements.
   *
   * @return array
   *   An array of string messages.
   */
  public function getRequirementErrors(): array {
    $errors = [];
    if ($count = $this->getOrphanedTriples()) {
      $errors[] = $this->translation->formatPlural($count, '- :count orphaned triple was detected.', '- :count orphaned triples were found.', [
        ':count' => $count,
      ]);
    }

    if ($graphs = $this->getLeftoverFederationGraphs()) {
      $errors[] = $this->t('- Leftover graphs were found: :graphs', [
        ':graphs' => implode(', ', $graphs),
      ]);
    }

    return $errors;
  }

  /**
   * Fetches the number of orphaned triples in the RDF entity main graphs.
   *
   * @return int
   *   The error message or an empty string if everything is ok.
   */
  protected function getOrphanedTriples(): int {
    $query = <<<QUERY
SELECT COUNT(*) as ?count
WHERE {
  GRAPH ?g {
    ?s ?p ?o .
    FILTER NOT EXISTS { ?s <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?o1 } .
    VALUES ?g {
      <http://joinup.eu/asset_distribution/published>
      <http://joinup.eu/asset_release/published>
      <http://joinup.eu/asset_release/draft>
      <http://joinup.eu/collection/published>
      <http://joinup.eu/collection/draft> 
      <http://joinup.eu/contact-information/published>
      <http://joinup.eu/licence/published>
      <http://joinup.eu/owner/published>
      <http://joinup.eu/provenance_activity>
      <http://joinup.eu/solution/published>
      <http://joinup.eu/solution/draft>
      <http://joinup.eu/spdx_licence/published> 
    }
  }
}
QUERY;
    return $this->sparql->query($query)->offsetGet(0)->count->getValue();
  }

  /**
   * Fetches the number of leftover graphs.
   *
   * @return array
   *   The leftover graphs.
   */
  protected function getLeftoverFederationGraphs(): array {
    $query = <<<QUERY
SELECT DISTINCT ?g WHERE { GRAPH ?g {?s ?p ?o} } ORDER BY ?g
QUERY;

    $graphs = $this->sparql->query($query);
    $errors = [];
    foreach ($graphs as $graph) {
      $uri = $graph->g->getUri();
      if (strpos($uri, 'http://adms-validator/') === 0) {
        $errors[] = $uri;
      }
    }

    return $errors;
  }

}
