<?php
namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\ConditionAggregate;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;

class Query  extends QueryBase implements QueryInterface {
  protected $rdf_base = NULL;
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
    // @todo Get this from entity bundle.
    $this->rdf_base = 'admssw:SoftwareProject';
    return $this
      ->setPrefixes()
      ->compile()
      ->addSort()
      ->finish()
      ->result();
  }

  protected function setPrefixes() {
    return $this;
  }

  protected function compile() {
    return $this;
  }

  protected function addSort() {
    return $this;
  }

  protected function finish() {
    return $this;
  }
  protected function result() {

    if ($this->count) {
      $query = 'SELECT count(?entity) AS ?count ';
    }
    else {
      $query = 'SELECT ?entity ';
    }
    $query .=
      'WHERE{' .
      '?entity rdf:type ?bundle.'.
      '?bundle rdfs:isDefinedBy <http://joinup.ec.europa.eu/asset/adms_foss/release/release100>.'.
      '}';

    $this->initializePager();
    if (!$this->count && $this->range) {
      $query .= '
      LIMIT ' . $this->range['length'] . '
      OFFSET ' . $this->range['start'];
    }


    if ($this->count) {
      $results = $this->connection->query($query);
      foreach ($results as $result) {
        return (string) $result->count;
      }
    }
    $uris = [];
    /** @var \EasyRdf_Http_Response $results */
    $results = $this->connection->query($query);
    foreach ($results as $result) {
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
}