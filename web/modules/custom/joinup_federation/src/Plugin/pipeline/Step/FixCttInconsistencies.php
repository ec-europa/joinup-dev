<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fix CTT Inconsistencies.
 *
 * @PipelineStep(
 *   id = "fix_ctt_inconsistencies",
 *   label = @Translation("Empty fields values"),
 * )
 */
class FixCttInconsistencies extends JoinupFederationStepPluginBase {

  use SparqlEntityStorageTrait;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['collection' => NULL] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    foreach ($this->getQueriesToRun() as $query) {
      $this->sparql->query($query);
    }
  }

  /**
   * Returns a list of queries that fix the CTT pipeline.
   *
   * @return array
   *   A list of queries.
   */
  protected function getQueriesToRun(): array {
    $return = [];

    // Contact information without email.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
INSERT { ?subject <http://www.w3.org/2006/vcard/ns#hasEmail> "dummy@example.com" }
WHERE {
	?subject a <http://www.w3.org/2006/vcard/ns#Kind>
	FILTER NOT EXISTS { ?subject <http://www.w3.org/2006/vcard/ns#hasEmail> ?email }
}
QUERY;

    // Solution type that are invalid.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/type> ?solution_type }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Dataset> .
	?subject <http://purl.org/dc/terms/type> ?solution_type .
	FILTER NOT EXISTS { ?solution_type <http://www.w3.org/2004/02/skos/core#inScheme> ?sceme }
}
QUERY;

    // Solution spatial terms that are invalid.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/spatial> ?spatial }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Dataset> .
	?subject <http://purl.org/dc/terms/spatial> ?spatial .
	FILTER NOT EXISTS { ?spatial <http://www.w3.org/2004/02/skos/core#inScheme> ?sceme }
}
QUERY;

    // Solution having related solutions that are invalid.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/relation> ?relation }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Dataset> .
	?subject <http://purl.org/dc/terms/relation> ?relation .
	FILTER NOT EXISTS { ?relation a <http://www.w3.org/ns/dcat#Dataset> }
}
QUERY;

    // Distribution that reference licenses that are invalid.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?distribution <http://purl.org/dc/terms/license> ?license }
INSERT { ?distribution <http://purl.org/dc/terms/license> <http://joinup.ec.europa.eu/category/licence/not-applicable> }
WHERE {
	?distribution a <http://www.w3.org/ns/dcat#Distribution> .
	?distribution <http://purl.org/dc/terms/license> ?license .
	FILTER NOT EXISTS { ?license a <http://purl.org/dc/terms/LicenseDocument> }
}
QUERY;

    // Contact information with empty English names.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
INSERT { ?subject <http://www.w3.org/2006/vcard/ns#fn> ?converted_property }
WHERE {
	?subject a <http://www.w3.org/2006/vcard/ns#Kind> .
	?subject <http://www.w3.org/2006/vcard/ns#fn> ?property .
        FILTER (LANG(?property) = 'es')
        MINUS {
           ?subject a <http://www.w3.org/2006/vcard/ns#Kind> .
       	   ?subject <http://www.w3.org/2006/vcard/ns#fn> ?label .
           FILTER (LANG(?label) = 'en')
        }
	BIND (concat('"', STR(?property), '"@en') AS ?converted_property)
}
QUERY;

    // Owners with empty english names.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
INSERT { ?subject <http://xmlns.com/foaf/0.1/name> ?converted_property }
WHERE {
	?subject a <http://xmlns.com/foaf/0.1/Agent> .
	?subject <http://xmlns.com/foaf/0.1/name> ?property .
        FILTER (LANG(?property) = 'es')
        MINUS {
           ?subject a <http://xmlns.com/foaf/0.1/Agent> .
       	   ?subject <http://xmlns.com/foaf/0.1/name> ?label .
           FILTER (LANG(?label) = 'en')
        }
	BIND (concat('"', STR(?property), '"@en') AS ?converted_property)
}
QUERY;

    // Solutions with empty English titles.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/title> ?label }
INSERT { ?subject <http://purl.org/dc/terms/title> ?converted_property }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Dataset> .
	?subject <http://purl.org/dc/terms/title> ?property .
	?subject <http://purl.org/dc/terms/title> ?label .
        FILTER (LANG(?property) = 'es')
        FILTER EXISTS {
           ?subject a <http://www.w3.org/ns/dcat#Dataset> .
       	   ?subject <http://purl.org/dc/terms/title> ?label .
           FILTER (LANG(?label) = 'en' AND STRLEN(STR(?label)) = 0)
        }
	BIND (concat('"', STR(?property), '"@en') AS ?converted_property)
}
QUERY;

    // Solutions with empty English descriptions.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/description> ?descr }
INSERT { ?subject <http://purl.org/dc/terms/description> ?converted_property }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Dataset> .
	?subject <http://purl.org/dc/terms/description> ?property .
	?subject <http://purl.org/dc/terms/description> ?descr .
        FILTER (LANG(?property) = 'es')
        FILTER EXISTS {
           ?subject a <http://www.w3.org/ns/dcat#Dataset> .
       	   ?subject <http://purl.org/dc/terms/description> ?descr .
           FILTER (LANG(?descr) = 'en' AND STRLEN(STR(?descr)) = 0)
        }
	BIND (concat('"', STR(?property), '"@en') AS ?converted_property)
}
QUERY;

    // Distributions with empty English titles.
    $return[] = <<<QUERY
WITH <http://joinup-federation/sink>
DELETE { ?subject <http://purl.org/dc/terms/title> ?label }
INSERT { ?subject <http://purl.org/dc/terms/title> ?converted_property }
WHERE {
	?subject a <http://www.w3.org/ns/dcat#Distribution> .
	?subject <http://purl.org/dc/terms/title> ?property .
	?subject <http://purl.org/dc/terms/title> ?label .
        FILTER (LANG(?property) = 'es')
        FILTER EXISTS {
           ?subject a <http://www.w3.org/ns/dcat#Distribution> .
       	   ?subject <http://purl.org/dc/terms/title> ?label .
           FILTER (LANG(?label) = 'en' AND STRLEN(STR(?label)) = 0)
        }
	BIND (concat('"', STR(?property), '"@en') AS ?converted_property)
}
QUERY;

    return $return;
  }

}
