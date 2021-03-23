<?php

namespace Drupal\sparql_entity_storage\Driver\Database\sparql;

use Drupal\Core\Database\StatementInterface;

/**
 * Represents a faked database statement object.
 *
 * The Drupal core database logger cannot be swapped because, instead of being
 * injected, is hardcoded in \Drupal\Core\Database\Database::startLog(). But the
 * \Drupal\Core\Database\Log::log() is expecting a database statement of type
 * \Drupal\Core\Database\StatementInterface as first argument and the SPARQL
 * database driver uses no StatementInterface class. We workaround this
 * limitation by faking a database statement object just to honour the logger
 * requirement. We use a statement stub that only stores the connection and the
 * query to be used when logging the event.
 *
 * This class extends also the \Iterator interface just to comply with the
 * PHPUnit tests. See
 * https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103.
 *
 * @see \Drupal\Core\Database\Database::startLog()
 * @see \Drupal\Core\Database\Log
 * @see \Drupal\Core\Database\StatementInterface
 * @see \Drupal\sparql_entity_storage\Driver\Database\sparql\Connection::log()
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
 */
class StatementStub implements \Iterator, StatementInterface {

  /**
   * The SPARQL query.
   *
   * @var string
   */
  protected $query;

  /**
   * Reference to the database connection object for this statement.
   *
   * The name $dbh is inherited from \PDOStatement.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\\sparql\ConnectionInterface
   */
  public $dbh;

  /**
   * Sets the query.
   *
   * @param string $query
   *   The SPARQL query.
   *
   * @return $this
   */
  public function setQuery(string $query): self {
    $this->query = $query;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryString(): string {
    return $this->query;
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\sparql_entity_storage\Driver\Database\\sparql\ConnectionInterface $connection
   *   The SPARQL connection.
   *
   * @return $this
   */
  public function setDatabaseConnection(ConnectionInterface $connection): self {
    $this->dbh = $connection;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {}

  /**
   * {@inheritdoc}
   */
  public function fetch($mode = NULL, $cursor_orientation = NULL, $cursor_offset = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {}

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {}

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {}

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {}

  /**
   * {@inheritdoc}
   */
  public function fetchObject() {}

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {}

  /**
   * {@inheritdoc}
   */
  public function rowCount() {}

  /**
   * {@inheritdoc}
   */
  public function next() {}

  /**
   * {@inheritdoc}
   */
  public function valid() {}

  /**
   * {@inheritdoc}
   */
  public function current() {}

  /**
   * {@inheritdoc}
   */
  public function rewind() {}

  /**
   * {@inheritdoc}
   */
  public function key() {}

}
