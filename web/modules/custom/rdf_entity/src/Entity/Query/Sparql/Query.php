<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\ConditionAggregate;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfFieldHandler;

/**
 * The base entity query class for Rdf entities.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The string query.
   *
   * @var string
   */
  public $query = '';

  /**
   * Indicates if preExecute() has already been called.
   *
   * @var bool
   */
  protected $prepared = FALSE;

  /**
   * The graphs from where the query is going to try and load entities from.
   *
   * The variable holds a plain array of graph uris.
   *
   * @var array
   *
   * @todo: Needs change to query graphs.
   */
  protected $graphs = NULL;

  /**
   * An array that is meant to hold the results.
   *
   * @var array
   */
  protected $results = NULL;

  /**
   * Entity storage.
   *
   * @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
   */
  protected $entityStorage = NULL;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The rdf graph handler service object.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandler
   */
  protected $graphHandler;

  /**
   * The rdf mapping handler service object.
   *
   * @var \Drupal\rdf_entity\RdfFieldHandler
   */
  protected $fieldHandler;

  /**
   * Constructs a query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $connection
   *   The database connection to run the query against.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service object.
   * @param \Drupal\rdf_entity\RdfGraphHandler $rdf_graph_handler
   *   The rdf graph handler service.
   * @param \Drupal\rdf_entity\RdfFieldHandler $rdf_field_handler
   *   The rdf mapping handler service.
   *
   * @throws \Exception
   *   Thrown when the storage passed is not an RdfEntitySparqlStorage.
   *
   * @todo: Is this exception check needed?
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces, EntityTypeManagerInterface $entity_type_manager, RdfGraphHandler $rdf_graph_handler, RdfFieldHandler $rdf_field_handler) {
    // Assign the handlers before calling the parent so that they can be passed
    // to the condition class properly.
    $this->graphHandler = $rdf_graph_handler;
    $this->fieldHandler = $rdf_field_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $this->entityTypeManager->getStorage($entity_type->id());
    $this->connection = $connection;
    parent::__construct($entity_type, $conjunction, $namespaces);

    if (!$this->entityStorage instanceof RdfEntitySparqlStorage) {
      throw new \Exception('Sparql storage is required for this query.');
    }

    // Set a unique tag for the rdf_entity queries.
    $this->addTag('rdf_entity');
    $this->addMetaData('entity_type', $this->getEntityType());
  }

  /**
   * Returns the graph handler service.
   *
   * @return \Drupal\rdf_entity\RdfGraphHandler
   *   The graph handler service.
   */
  public function getGraphHandler() {
    return $this->graphHandler;
  }

  /**
   * Returns the mapping handler service.
   *
   * @return \Drupal\rdf_entity\RdfFieldHandler
   *   The mapping handler service.
   */
  public function getfieldHandler() {
    return $this->fieldHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Returns the entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type object.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Returns the entity type storage.
   *
   * @return \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
   *   The entity type storage.
   */
  public function getEntityStorage() {
    return $this->entityStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function count($field = TRUE) {
    $this->count = $field;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    return $this
      ->prepare()
      ->compile()
      ->addSort()
      ->addPager()
      ->run()
      ->result();
  }

  /**
   * Set the graph types for the query.
   *
   * This allows the filtering of graphs on the query level. There are two ways
   * to filter the results:
   * - Set the graph types in this method.
   * - Set the request graphs in the storage level.
   * The query graph filter that is set below is filtering the graphs
   * that the query will run on, so this makes this filter a runtime filter.
   * After the results are retrieved, the storage will further filter the
   * results based on the request graphs set for the entities.
   *
   * @param array $graph_types
   *   An array of graphs ids to be passed into the query.
   *
   * @todo: When a condition is set on the bundle, this graphs should be
   * filtered accordingly.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::setRequestGraphs()
   * @see \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage::processGraphResults()
   */
  public function setGraphType(array $graph_types = ['default']) {
    $this->graphs = $this->entityStorage->getGraphHandler()->getEntityTypeGraphUrisList($this->entityType->getBundleEntityType(), $graph_types);
  }

  /**
   * Initialize the query.
   *
   * @return $this
   */
  protected function prepare() {
    // Running as count query?
    if ($this->count) {
      if (is_string($this->count)) {
        $this->query = 'SELECT count(' . $this->count . ') AS ?count ';
      }
      else {
        $this->query = 'SELECT count(?entity) AS ?count ';
      }
    }
    else {
      $this->query = 'SELECT DISTINCT(?entity) ';
    }
    $this->query .= "\n";

    if ($this->graphs) {
      foreach ($this->graphs as $graph) {
        $this->query .= 'FROM <' . $graph . '>' . "\n";
      }
    }
    return $this;
  }

  /**
   * Add the registered conditions to the WHERE clause.
   *
   * @return $this
   */
  protected function compile() {
    // Modules may alter all queries or only those having a particular tag.
    if (isset($this->alterTags)) {
      // Remap the entity reference default tag to the rdf_entity reference
      // because the first one requires that the query is an instance of the
      // SelectInterface.
      // @todo: Maybe overwrite the default selection class?
      if (isset($this->alterTags['entity_reference'])) {
        $this->alterTags['rdf_entity_reference'] = $this->alterTags['entity_reference'];
        unset($this->alterTags['entity_reference']);
      }
      $hooks = ['query'];
      foreach ($this->alterTags as $tag => $value) {
        $hooks[] = 'query_' . $tag;
      }
      \Drupal::moduleHandler()->alter($hooks, $this);
    }

    $this->condition->compile($this);
    $this->query .= "WHERE {\n" . $this->condition->toString() . "\n}";
    return $this;
  }

  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\Query
   *   Returns the called object.
   */
  protected function addSort() {
    if ($this->count) {
      $this->sort = [];
    }
    // Simple sorting. For the POC, only uri's and bundles are supported.
    // @todo Implement sorting on bundle fields?
    if ($this->sort) {
      // @todo Support multiple sort conditions.
      $sort = array_pop($this->sort);
      // @todo Can we use the field mapper here as well?
      // Consider looping over the sort criteria in both the compile step and
      // here: We can add ?entity <pred> ?sort_1 in the condition, and
      // ORDER BY ASC ?sort_1 here (I think).
      switch ($sort['field']) {
        case 'id':
          $this->query .= 'ORDER BY ' . $sort['direction'] . ' (?entity)';
          break;

        case 'rid':
          $this->query .= 'ORDER BY ' . $sort['direction'] . ' (?bundle)';
          break;
      }
    }
    return $this;
  }

  /**
   * Add pager to query.
   */
  protected function addPager() {
    $this->initializePager();
    if (!$this->count && $this->range) {
      $this->query .= 'LIMIT ' . $this->range['length'] . "\n";
      $this->query .= 'OFFSET ' . $this->range['start'] . "\n";
    }
    return $this;
  }

  /**
   * Commit the query to the backend.
   */
  protected function run() {
    /** @var \EasyRdf_Http_Response $results */
    $this->results = $this->connection->query($this->query);
    return $this;
  }

  /**
   * Do the actual query building.
   */
  protected function result() {
    // Count query.
    if ($this->count) {
      foreach ($this->results as $result) {
        return (string) $result->count;
      }
    }
    $uris = [];

    // SELECT query.
    foreach ($this->results as $result) {
      // If the query does not return any results, EasyRdf_Sparql_Result still
      // contains an empty result object. If this is the case, skip it.
      if (!empty((array) $result)) {
        $uri = (string) $result->entity;
        $uris[$uri] = $uri;
      }
    }
    return $uris;
  }

  /**
   * Returns the array of conditions.
   *
   * @return array
   *   The array of conditions.
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * {@inheritdoc}
   */
  public function existsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->exists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExistsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->notExists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND') {
    return new ConditionAggregate($conjunction, $this);
  }

  /**
   * {@inheritdoc}
   */
  protected function conditionGroupFactory($conjunction = 'AND') {
    $class = static::getClass($this->namespaces, 'SparqlCondition');
    return new $class($conjunction, $this, $this->namespaces, $this->graphHandler, $this->fieldHandler);
  }

  /**
   * Return the query string for debugging help.
   *
   * @return string
   *   Query.
   */
  public function __toString() {
    return $this->query;
  }

}
