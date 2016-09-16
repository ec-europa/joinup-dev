<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\ConditionAggregate;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * The base entity query class for Rdf entities.
 */
class Query extends QueryBase implements QueryInterface {
  protected $sortQuery = NULL;

  public $query = '';

  /**
   * Filters.
   *
   * @var \Drupal\Core\Entity\Query\ConditionInterface
   */
  protected $filter;

  protected $graphs = NULL;

  protected $results = NULL;

  /**
   * True if a type filter has been already added to the query.
   *
   * Currently there is no easy method to avoid multiple conditions on rdf type,
   * so we keep track if a condition has already added such filter.
   *
   * @var bool
   */
  protected $filterAdded = FALSE;

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
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service object.
   *
   * @throws \Exception
   *   Thrown when the storage passed is not an RdfEntitySparqlStorage.
   * @todo: Is this exception check needed?
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces, EntityTypeManager $entity_type_manager) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->filter = new SparqlFilter();
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $this->entityTypeManager->getStorage($this->entityTypeId);

    if (!$this->entityStorage instanceof RdfEntitySparqlStorage) {
      throw new \Exception('Sparql storage is required for this query.');
    }
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
      ->addConditions()
      ->addSort()
      ->addPager()
      ->run()
      ->result();
  }

  /**
   * Set the graph type.
   */
  public function setGraphType($graph_types = ['default']) {
    $this->graphs = $this->entityStorage->getGraphs($graph_types);
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
      $this->query = 'SELECT ?entity ';
    }
    $this->query .= "\n";

    if ($this->graphs) {
      foreach (array_keys($this->graphs) as $graph) {
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
  protected function addConditions() {
    $this->query .=
      "WHERE{\n";
    $this->condition->compile($this);
    $this->filter->compile($this);
    $this->query .= "}\n";

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function filter($filter, $type = 'FILTER') {
    $this->filter->filter($filter, $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = '=', $langcode = NULL) {
    $key = $property . '-' . $operator;
    $field_storage_definitions = \Drupal::service('entity.manager')
      ->getFieldStorageDefinitions($this->entityTypeId);
    /*
     * Ok, so what is all this:
     * We need to convert our conditions into some sparql compatible conditions.
     */
    $bundle = $this->entityType->getKey('bundle');
    $id = $this->entityType->getKey('id');
    $label = $this->entityType->getKey('label');
    switch ($key) {
      // @todo Limit the graphs here to the set bundles.
      case  $bundle . '-IN':
        $rdf_bundles = $this->entityStorage->getRdfBundleList($value);
        if ($rdf_bundles) {
          $this->condition->condition('?entity', '?bundlepredicate', '?type');
          $this->filterAdded = TRUE;
          $predicates = "(<" . implode(">, <", $this->entityStorage->bundlePredicate()) . ">)";
          $this->filter->filter('?bundlepredicate IN ' . $predicates);
          $this->filter->filter('?type IN ' . $rdf_bundles);
        }
        return $this;

      case $bundle . '-=':
        $mapping = $this->entityStorage->getRdfBundleMapping();
        $mapping = $mapping['rdf_entity'];
        $bundle = $mapping[$value];
        if ($bundle) {
          $this->condition->condition('?entity', '?bundlepredicate', SparqlArg::uri($bundle));
          $predicates = "(<" . implode(">, <", $this->entityStorage->bundlePredicate()) . ">)";
          $this->filter->filter('?bundlepredicate IN ' . $predicates);
          $this->filterAdded = TRUE;
        }
        return $this;

      case $id . '-IN':
        if ($value) {
          $ids_list = "(<" . implode(">, <", $value) . ">)";
          if (!$this->filterAdded) {
            $this->condition->condition('?entity', '?bundlepredicate', '?type');
            $predicates = "(<" . implode(">, <", $this->entityStorage->bundlePredicate()) . ">)";
            $this->filter->filter('?bundlepredicate IN ' . $predicates);
            $this->filterAdded = TRUE;
          }
          $this->filter->filter('?entity IN ' . $ids_list);
        }
        return $this;

      case $id . '-NOT IN':
      case $id . '-<>':
        if ($value) {
          if (is_array($value)) {
            $ids_list = "(<" . implode(">, <", $value) . ">)";
          }
          else {
            $ids_list = "(<" . $value . ">)";
          }

          if (!$this->filterAdded) {
            $this->condition->condition('?entity', '?bundlepredicate', '?type');
            $predicates = "(<" . implode(">, <", $this->entityStorage->bundlePredicate()) . ">)";
            $this->filter->filter('?bundlepredicate IN ' . $predicates);
            $this->filterAdded = TRUE;
          }
          $this->filter->filter('!(?entity IN ' . $ids_list . ')');
        }
        return $this;

      case $id . '-=':
        if (!$value) {
          return $this;
        }
        $id = '<' . $value . '>';
        if (!$this->filterAdded) {
          $this->condition->condition('?entity', '?bundlepredicate', '?type');
          $predicates = "(<" . implode(">, <", $this->entityStorage->bundlePredicate()) . ">)";
          $this->filter->filter('?bundlepredicate IN ' . $predicates);
          $this->filterAdded = TRUE;
        }
        $this->filter->filter('?entity IN ' . SparqlArg::literal($id));
        break;

      case $label . '-=':
        preg_match('/\((.*?)\)/', $value, $matches);
        $matching = array_pop($matches);
        if ($matching) {
          $ids = "(<$matching>)";
          $this->filter->filter('?entity IN ' . $ids);
        }
        else {
          if (file_valid_uri($value)) {
            $ids = "(<$value>)";
            $this->filter->filter('?entity IN ' . $ids);
          }
          else {
            $mapping = $this->entityStorage->getLabelMapping();
            $label_list = "(<" . implode(">, <", array_unique(array_keys($mapping[$this->entityTypeId]))) . ">)";
            $this->condition->condition('?entity', '?label_type', '?label');
            $this->filter->filter('?label_type IN ' . $label_list);
            $this->filter->filter('str(?label) = "' . $value . '"');
          }
        }

        return $this;

      case $label . '-CONTAINS':
        $mapping = $this->entityStorage->getLabelMapping();
        $label_list = "(<" . implode(">, <", array_unique(array_keys($mapping[$this->entityTypeId]))) . ">)";
        $this->condition->condition('?entity', '?label_type', '?label');
        $this->filter->filter('?label_type IN ' . $label_list);
        if ($value) {
          $this->filter->filter('regex(?label, "' . $value . '", "i")');
          $this->filter->filter('(lang(?label) = "" || langMatches(lang(?label), "EN"))');
        }
        return $this;

      case '_field_exists-EXISTS':
      case '_field_exists-NOT EXISTS':
        $field_rdf_name = $this->getFieldRdfPropertyName($value, $field_storage_definitions);

        if (!UrlHelper::isValid($field_rdf_name, TRUE) === FALSE) {
          $field_rdf_name = SparqlArg::uri($field_rdf_name);
        }
        if ($field_rdf_name) {
          $this->filter('?entity ' . $field_rdf_name . ' ?c', 'FILTER ' . $operator);
        }
        return $this;

    }
    if ($operator == '=') {
      if (!$value) {
        return $this;
      }

      // @todo this code will be handled in ISAICP-2631
      if (strpos($property, '.') !== FALSE) {
        list ($field_name, $column) = explode('.', $property);
      }
      else {
        $field_name = $property;
      }

      $field_rdf_name = $this->getFieldRdfPropertyName($field_name, $field_storage_definitions);

      if (!UrlHelper::isValid($value, TRUE) === FALSE) {
        $value = SparqlArg::uri($value);
      }
      else {
        $value = SparqlArg::literal($value);
      }
      $this->condition->condition('?entity', SparqlArg::uri($field_rdf_name), $value);
    }

    return $this;
  }

  /**
   * Returns an rdf property name for the given field.
   *
   * @param string $field_name
   *   The machine name of the field.
   * @param array $field_storage_definitions
   *   The field storage definition Item.
   *
   * @return string
   *   The property name of the field. If it is a uri, wrap it with '<', '>'.
   *
   * @throws \Exception
   *   Thrown when the field has not a valid rdf property name.
   */
  public function getFieldRdfPropertyName($field_name, $field_storage_definitions) {
    if (empty($field_storage_definitions[$field_name])) {
      throw new \Exception('Unknown field ' . $field_name);
    }
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = $field_storage_definitions[$field_name];
    if (empty($column)) {
      $column = $field_storage->getMainPropertyName();
    }
    $field_rdf_name = $field_storage->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE);
    if (empty($field_rdf_name)) {
      throw new \Exception('No 3rd party field settings for ' . $field_name);
    }

    return $field_rdf_name;
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
