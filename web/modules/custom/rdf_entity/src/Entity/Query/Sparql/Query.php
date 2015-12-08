<?php

/**
 * @file
 * Build queries to the triple store.
 */

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\ConditionAggregate;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;

/**
 * The base entity query class for Rdf entities.
 */
class Query extends QueryBase implements QueryInterface {
  protected $sortQuery = NULL;

  public $query = NULL;

  protected $results = NULL;
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
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->connection = $connection;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    return $this
      ->prepare()
      ->addConditions()
      ->addSort()
      ->addPager()
      ->run()
      ->result();
  }

  /**
   * Initialize the query.
   *
   * @return $this
   */
  protected function prepare() {
    // Running as count query?
    if ($this->count) {
      $this->query = 'SELECT count(?entity) AS ?count ';
    }
    else {
      $this->query = 'SELECT ?entity ';
    }
    $this->query .= "\n";
    return $this;
  }

  /**
   * Add the registered conditions to the WHERE clause.
   *
   * @return $this
   */
  protected function addConditions() {
    $this->query .=
      "WHERE{\n";
    $this->condition->compile($this);
    $this->query .= "}\n";
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->condition->condition($property, $value, $operator, $langcode);
    return $this;
  }
  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\Query
   *   Returns the called object.
   */
  protected function addSort() {
    if (!$this->sortQuery) {
      return $this;
    }
    if ($this->count) {
      $this->sort = array();
    }
    // Simple sorting. For the POC, only uri's and bundles are supported.
    // @todo Implement sorting on bundle fields?
    if ($this->sort) {
      $sort = array_pop($this->sort);
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
      $this->query .= 'LIMIT ' . $this->range['length'];
      $this->query .= 'OFFSET ' . $this->range['start'];
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
      $uri = (string) $result->entity;
      $uris[$uri] = $uri;

    }
    return $uris;
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
    return new $class($conjunction, $this, $this->namespaces);
  }

}
