<?php

declare(strict_types = 1);

namespace Drupal\rdf_schema_field_validation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\Exception\UnmappedFieldException;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;
use Drupal\sparql_entity_storage\SparqlMappingInterface;

/**
 * A service that validates that fields are defined in a schema.
 */
class SchemaFieldValidator implements SchemaFieldValidatorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
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
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface
   */
  protected $fieldHanlder;

  /**
   * Constructs a new SchemaFieldValidator object.
   *
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $field_hanlder
   *   The rdf field handler service.
   */
  public function __construct(ConnectionInterface $sparql_endpoint, EntityTypeManagerInterface $entity_type_manager, SparqlEntityStorageFieldHandlerInterface $field_hanlder) {
    $this->sparqlEndpoint = $sparql_endpoint;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldHanlder = $field_hanlder;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefinedInSchema(string $entity_type_id, string $bundle, string $field_name, string $column_name = ''): bool {
    $mapping = $this->getEntityMapping($entity_type_id, $bundle);
    if (empty($mapping) || empty($properties = $mapping->getThirdPartySettings('rdf_schema_field_validation'))) {
      throw new \Exception("The entity does not appear to have mapped properties.");
    }

    try {
      $predicate = $this->fieldHanlder->getFieldPredicates($entity_type_id, $field_name, $column_name);
    }
    catch (UnmappedFieldException $exception) {
      // An unmapped field is not defined in schema.
      return FALSE;
    }

    // Mappings with empty predicates are not defined in schema.
    if (empty($predicate[$bundle])) {
      return FALSE;
    }

    $rdf_type = $mapping->getRdfType();
    $query = $this->getQuery($properties['graph'], $properties['class'], $properties['property_predicates'], $rdf_type, $predicate[$bundle]);

    return $this->sparqlEndpoint->query($query)->isTrue();
  }

  /**
   * Retrieves an SparqlMapping entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity type id.
   *
   * @return \Drupal\sparql_entity_storage\Entity\SparqlMapping|null
   *   The rdf entity mapping or null, if none is found.
   */
  protected function getEntityMapping(string $entity_type_id, string $bundle): ?SparqlMappingInterface {
    $id = "{$entity_type_id}.{$bundle}";
    /** @var \Drupal\sparql_entity_storage\SparqlMappingInterface $mapping */
    $mapping = $this->entityTypeManager->getStorage('sparql_mapping')->load($id);
    return $mapping;
  }

  /**
   * Retrieves a query that asks whether a field is defined in the schema.
   *
   * @param string $graph
   *   The graph uri.
   * @param string $class
   *   The Uri that defines a class object.
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
  protected function getQuery(string $graph, string $class, array $property_predicates, string $rdf_type, string $field_iri): string {
    $search = [
      '@graph',
      '@class',
      '@property_predicates',
      '@rdf_type',
      '@field_iri',
    ];
    $replace = [
      SparqlArg::uri($graph),
      SparqlArg::uri($class),
      SparqlArg::serializeUris($property_predicates, ' '),
      SparqlArg::uri($rdf_type),
      SparqlArg::uri($field_iri),
    ];

    // The query will ask whether a field belongs to an ontology that itself is
    // defined as a class.
    $query = <<<QUERY
ASK { 
  GRAPH @graph { 
    @rdf_type a @class .
    @field_iri ?property_predicates @rdf_type .
    VALUES ?property_predicates { @property_predicates } .  
  } 
}
QUERY;

    return str_replace($search, $replace, $query);
  }

}
