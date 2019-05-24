<?php

namespace Drupal\Tests\rdf_schema_field_validation\Kernel;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;
use EasyRdf\Graph;

/**
 * Suite that tests the rdf field schema validation service.
 *
 * @group rdf_schema_field_validation
 */
class RdfSchemaFieldValidationTest extends JoinupKernelTestBase {

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $spaqlEndpoint;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManger;

  /**
   * The schema definition graph URI.
   *
   * @var string
   */
  protected $definitionUri = 'http://example.com/dummy/schema-definition';

  /**
   * The field schema validation service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $schemaFieldValidator;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'rdf_entity',
    'rdf_entity_test',
    'rdf_schema_field_validation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->spaqlEndpoint = $this->container->get('sparql_endpoint');
    $this->entityTypeManger = $this->container->get('entity_type.manager');
    $this->schemaFieldValidator = $this->container->get('rdf_schema_field_validation.schema_field_validator');

    /** @var \Drupal\sparql_entity_storage\SparqlMappingInterface $dummy_mapping */
    $dummy_mapping = $this->entityTypeManger->getStorage('sparql_mapping')->load("rdf_entity.dummy");
    $dummy_mapping->setThirdPartySetting('rdf_schema_field_validation', 'property_predicates', ['http://www.w3.org/2000/01/rdf-schema#domain']);
    $dummy_mapping->setThirdPartySetting('rdf_schema_field_validation', 'graph', $this->definitionUri);
    $dummy_mapping->setThirdPartySetting('rdf_schema_field_validation', 'class', 'http://www.w3.org/2000/01/rdf-schema#Class');
    $dummy_mapping->save();

    $filename = __DIR__ . '/../../fixtures/dummy_definition.rdf';
    if (!is_file($filename)) {
      throw new \Exception("Definition file not found.");
    }
    $data = file_get_contents($filename);
    $format = 'rdfxml';
    $graph = new Graph($this->definitionUri, $data, $format);
    $graph_uri = SparqlArg::uri($this->definitionUri);
    $query = "INSERT DATA INTO $graph_uri {\n";
    $query .= $graph->serialise('ntriples') . "\n";
    $query .= '}';
    $this->spaqlEndpoint->update($query);
  }

  /**
   * Asserts that fields belong or don't belong to a defined schema.
   */
  public function testFieldSchema() {
    $this->assertTrue($this->schemaFieldValidator->isDefinedInSchema('rdf_entity', 'dummy', 'field_text'));
    $this->assertFalse($this->schemaFieldValidator->isDefinedInSchema('rdf_entity', 'dummy', 'field_text', 'format'));
    $this->assertFalse($this->schemaFieldValidator->isDefinedInSchema('rdf_entity', 'dummy', 'label'));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $query = <<<EndOfQuery
DELETE {
  GRAPH <{$this->definitionUri}> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <{$this->definitionUri}> {
    ?entity ?field ?value
  }
}
EndOfQuery;
    $this->sparql->query($query);
    parent::tearDown();
  }

}
