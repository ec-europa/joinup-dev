<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\Sql\ConditionAggregate;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;

/**
 * The base entity query class for SPARQL stored entities.
 */
class Query extends QueryBase implements SparqlQueryInterface {

  /**
   * The connection object.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
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
   * The graph IDs from where the query is going load entities from.
   *
   * If the value is NULL, the query will load entities from all graphs.
   *
   * @var string[]|null
   */
  protected $graphIds;

  /**
   * An array that is meant to hold the results.
   *
   * @var array
   */
  protected $results = NULL;

  /**
   * The SPARQL entity storage.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The SPARQL graph handler service object.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * The SPARQL field mapping handler service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface
   */
  protected $fieldHandler;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type id key.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The entity type bundle key, if any.
   *
   * @var string|false
   */
  protected $bundleKey;

  /**
   * Constructs a query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $connection
   *   The database connection to run the query against.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service object.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler
   *   The SPARQL graph handler service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $sparql_field_handler
   *   The SPARQL field mapping handler service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, ConnectionInterface $connection, array $namespaces, EntityTypeManagerInterface $entity_type_manager, SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler, SparqlEntityStorageFieldHandlerInterface $sparql_field_handler, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    // Assign the handlers before calling the parent so that they can be passed
    // to the condition class properly.
    $this->graphHandler = $sparql_graph_handler;
    $this->fieldHandler = $sparql_field_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;

    parent::__construct($entity_type, $conjunction, $namespaces);

    $this->bundleKey = $entity_type->getKey('bundle');
    $this->idKey = $entity_type->getKey('id');

    // Set a unique tag for the SPARQL queries.
    $this->addTag('sparql');
    $this->addMetaData('entity_type', $this->entityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): EntityTypeInterface {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStorage(): SparqlEntityStorageInterface {
    if (!isset($this->entityStorage)) {
      $this->entityStorage = $this->entityTypeManager->getStorage($this->getEntityTypeId());
    }
    return $this->entityStorage;
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
   * {@inheritdoc}
   */
  public function graphs(array $graph_ids = NULL): SparqlQueryInterface {
    $this->graphIds = $graph_ids;
    return $this;
  }

  /**
   * Initialize the query.
   *
   * @return $this
   */
  protected function prepare(): SparqlQueryInterface {
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

    if (!$this->graphIds) {
      // Allow all default graphs for this entity type.
      $this->graphIds = $this->graphHandler->getEntityTypeDefaultGraphIds($this->getEntityTypeId());
    }

    foreach ($this->getGraphUris() as $graph_uri) {
      $this->query .= "FROM <$graph_uri>\n";
    }

    foreach ($this->sort as $data) {
      if (in_array($data['field'], [$this->idKey, $this->bundleKey])) {
        continue;
      }
      // Add a requirement for each sorting criteria.
      $this->addFieldMappingRequirement($this->entityTypeId, $data['field']);
    }

    return $this;
  }

  /**
   * Add the registered conditions to the WHERE clause.
   *
   * @return $this
   */
  protected function compile(): SparqlQueryInterface {
    // Modules may alter all queries or only those having a particular tag.
    if (isset($this->alterTags)) {
      // Remap the entity reference default tag to the SPARQL entity reference
      // because the first one requires that the query is an instance of the
      // SelectInterface.
      // @todo Maybe overwrite the default selection class?
      if (isset($this->alterTags['entity_reference'])) {
        $this->alterTags['sparql_reference'] = $this->alterTags['entity_reference'];
        unset($this->alterTags['entity_reference']);
      }
      $hooks = ['query'];
      foreach ($this->alterTags as $tag => $value) {
        $hooks[] = 'query_' . $tag;
      }
      $this->moduleHandler->alter($hooks, $this);
    }

    $this->condition->compile($this);
    $this->query .= "WHERE {\n{$this->condition}\n}";

    return $this;
  }

  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\sparql_entity_storage\Entity\Query\Sparql\Query
   *   Returns the called object.
   */
  protected function addSort(): SparqlQueryInterface {
    if ($this->count) {
      $this->sort = [];
    }

    if (empty($this->sort)) {
      return $this;
    }

    $fragments = [];
    foreach ($this->sort as $data) {
      $field = $data['field'];
      if ($field === $this->idKey) {
        $field = SparqlCondition::ID_KEY;
      }
      elseif ($field === $this->bundleKey) {
        $field = SparqlArg::toVar($field);
      }
      else {
        // Build the property to sort by just like it is build in condition().
        // @see: \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlCondition::condition.
        $field_name_parts = explode('.', $field);
        $field = $field_name_parts[0];
        $column = isset($field_name_parts[1]) ? $field_name_parts[1] : $this->fieldHandler->getFieldMainProperty($this->getEntityTypeId(), $field);
        $field = SparqlArg::toVar("{$field}__{$column}");
      }
      $fragments[] = empty($data['direction']) ? $field : "{$data['direction']}({$field})";
    }

    $this->query .= "\nORDER BY " . implode(', ', $fragments);
    return $this;
  }

  /**
   * Add pager to query.
   */
  protected function addPager(): SparqlQueryInterface {
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
  protected function run(): SparqlQueryInterface {
    /** @var \EasyRdf\Sparql\Result $results */
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
      // If the query does not return any results, EasyRdf\Sparql\Result still
      // contains an empty result object. If this is the case, skip it.
      if (!empty((array) $result)) {
        $uri = (string) $result->entity;
        $uris[$uri] = $uri;
      }
    }
    return $uris;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    $direction = strtoupper($direction);
    if (!in_array($direction, ['ASC', 'DESC'])) {
      throw new \RuntimeException('Only "ASC" and "DESC" are allowed as sort order.');
    }
    return parent::sort($field, $direction, $langcode);
  }

  /**
   * Returns the array of conditions.
   *
   * @return array
   *   The array of conditions.
   */
  public function &conditions(): array {
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
    return new $class($conjunction, $this, $this->namespaces, $this->graphHandler, $this->fieldHandler, $this->languageManager);
  }

  /**
   * Wrapper method to set a mapping requirement in the conditions group.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   */
  protected function addFieldMappingRequirement(string $entity_type_id, string $field): void {
    $this->condition->addFieldMappingRequirement($entity_type_id, $field);
  }

  /**
   * Returns a list of graph URIs that should be queried.
   *
   * @return string[]
   *   List of graph URIs.
   */
  protected function getGraphUris(): array {
    if ($bundle_conditions = $this->getBundleConditions()) {
      // When the query has at least a bundle condition, we optimize the list of
      // graphs to be searched.
      $bundle_uris = ['IN' => [], 'NOT IN' => []];
      $entity_type_graph_uris = $this->graphHandler->getEntityTypeGraphUris($this->getEntityTypeId());
      foreach ($bundle_conditions as $type => $bundle_ids) {
        foreach ($bundle_ids as $bundle_id) {
          foreach (array_values(array_intersect_key($entity_type_graph_uris[$bundle_id], array_flip($this->graphIds))) as $uri) {
            $bundle_uris[$type][] = $uri;
          }
        }
      }
      $bundle_uris['IN'] = $bundle_uris['IN'] ?: array_keys($this->graphHandler->getEntityTypeGraphUrisFlatList($this->getEntityTypeId(), $this->graphIds));
      return array_diff($bundle_uris['IN'], $bundle_uris['NOT IN']);
    }
    return array_keys($this->graphHandler->getEntityTypeGraphUrisFlatList($this->getEntityTypeId(), $this->graphIds));
  }

  /**
   * Returns the bundle conditions, if any.
   *
   * @return array
   *   The bundle conditions with two keys, 'IN' and/or 'NOT IN'. If the entity
   *   type supports no bundle or there are no bundle conditions, an empty array
   *   is returned.
   */
  protected function getBundleConditions(): array {
    $conditions = [];
    if ($this->bundleKey) {
      foreach ($this->conditions() as $condition) {
        if ($condition['field'] === $this->bundleKey) {
          foreach ($condition['value'] as $bundle_id) {
            if (!isset($conditions[$condition['operator']])) {
              $conditions[$condition['operator']] = [];
            }
            if (array_search($bundle_id, $conditions[$condition['operator']]) === FALSE) {
              $conditions[$condition['operator']][] = $bundle_id;
            }
          }
        }
      }
    }
    return $conditions;
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
