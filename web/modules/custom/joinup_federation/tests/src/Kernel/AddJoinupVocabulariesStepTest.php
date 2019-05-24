<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\taxonomy\Entity\Vocabulary;
use EasyRdf\Graph;

/**
 * Tests the 'add_joinup_vocabularies' process step plugin.
 *
 * @group joinup_federation
 */
class AddJoinupVocabulariesStepTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getUsedStepPlugins(): array {
    return ['add_joinup_vocabularies' => []];
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_taxonomy',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the language vocabulary and mapping.
    Vocabulary::create(['vid' => 'language', 'name' => 'Language'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../joinup_core/config/install/sparql_entity_storage.mapping.taxonomy_term.language.yml'));
    SparqlMapping::create($mapping)->save();
  }

  /**
   * Test ADMSv2 changes.
   */
  public function test() {
    $query = <<<Query
SELECT DISTINCT (?subject)
FROM NAMED <{$this->getTestingGraphs()['sink_plus_taxo']}>
WHERE {
  GRAPH <{$this->getTestingGraphs()['sink_plus_taxo']}> {
    ?subject ?predicate ?object .
    <http://publications.europa.eu/resource/authority/language/ENG> <http://www.w3.org/2004/02/skos/core#inScheme> <http://publications.europa.eu/resource/authority/language> .
  }
}
Query;

    $graph = new Graph(static::getTestingGraphs()['sink']);
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph);

    // Check that the language vocabulary is not in the 'sink_plus_taxo graph'
    // before executing the step.
    $this->assertCount(0, $this->sparql->query($query));

    $this->runPipelineStep('add_joinup_vocabularies');

    // Check that the language vocabulary is in the 'sink_plus_taxo graph' after
    // executing the step.
    $this->assertGreaterThan(0, $this->sparql->query($query)->count());
  }

}
