<?php

namespace Drupal\rdf_ff;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_entity\RdfEntityMappingInterface;
use Drupal\rdf_entity\RdfFieldHandler;

/**
 * A service that validates that fields are defined in a schema.
 */
class SchemaFieldValidator implements SchemaFieldValidatorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparqlEndpoint;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The rdf field handler service.
   *
   * @var \Drupal\rdf_entity\RdfFieldHandler
   */
  protected $fieldHanlder;

  /**
   * Constructs a new SchemaFieldValidator object.
   *
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql_endpoint
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rdf_entity\RdfFieldHandler $field_hanlder
   *   The rdf field handler service.
   */
  public function __construct(Connection $sparql_endpoint, EntityTypeManagerInterface $entity_type_manager, RdfFieldHandler $field_hanlder) {
    $this->sparqlEndpoint = $sparql_endpoint;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldHanlder = $field_hanlder;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Thrown if the entity does not have mapped properties.
   */
  public function isDefinedInSchema($entity_type_id, $bundle, $field_name, $column_name = '') {
    $mapping = $this->getEntityMapping($entity_type_id, $bundle);
    if (empty($mapping) || empty($properties = $mapping->getThirdPartySettings('rdf_ff'))) {
      throw new \Exception("The entity does not appear to have mapped properties.");
    }

    $predicate = $this->fieldHanlder->getFieldPredicates($entity_type_id, $field_name, $column_name);
    $rdf_type = $mapping->getRdfType();
    $query = $this->getQuery($properties['graph'], $properties['property_predicates'], $rdf_type, $predicate[$bundle]);

    return $this->sparqlEndpoint->query($query)->isTrue();
  }

  /**
   * Retrieves an RdfEntityMapping entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity type id.
   *
   * @return \Drupal\rdf_entity\RdfEntityMappingInterface|null
   *   The rdf entity mapping or null, if none is found.
   */
  protected function getEntityMapping($entity_type_id, $bundle): ?RdfEntityMappingInterface {
    $id = "{$entity_type_id}.{$bundle}";
    /** @var RdfEntityMappingInterface $mapping */
    $mapping = $this->entityTypeManager->getStorage('rdf_entity_mapping')->load($id);
    return $mapping;
  }

  /**
   * Retrieves a query that asks whether a field is defined in the schema.
   *
   * @param string $graph
   *   The graph uri.
   * @param array $property_predicates
   *   A list of predicates that can be used to declare that a field belongs to
   *   a class.
   * @param string $rdf_type
   *   The uri of the bundle.
   * @param string $field_iri
   *   The field mapped property.
   *
   * @return string
   *   The query string.
   */
  protected function getQuery($graph, $property_predicates, $rdf_type, $field_iri): string {
    $search = ['@graph', '@property_predicates', '@rdf_type', '@field_iri'];
    $replace = [
      SparqlArg::uri($graph),
      SparqlArg::serializeUris($property_predicates, ' '),
      SparqlArg::uri($rdf_type),
      SparqlArg::uri($field_iri),
    ];

    $query = <<<QUERY
ASK { 
  GRAPH @graph { 
    ?entity_id a @rdf_type .
    @field_iri ?property_predicates ?entity_id .
    VALUES ?property_predicates { @property_predicates } .  
  } 
}
QUERY;

    return str_replace($search, $replace, $query);
  }

}
