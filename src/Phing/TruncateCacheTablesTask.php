<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

require_once 'phing/tasks/ext/pdo/PDOTask.php';

/**
 * Phing task to truncate Drupal's cache tables.
 */
class TruncateCacheTablesTask extends \PDOTask {

  /**
   * The PDO connection.
   *
   * @var \PDO
   */
  private $connection;

  /**
   * {@inheritdoc}
   */
  public function main() {
    $this->connection = $this->getConnection();

    foreach ($this->getCacheTables() as $table) {
      $this->truncateTable($table);
    }
  }

  /**
   * Returns a list of cache table names.
   *
   * @return array
   *   An indexed array of cache table names.
   */
  protected function getCacheTables(): array {
    // We cannot query the Drupal API at this point to get the full list of
    // cache tables, so just return all tables starting with 'cache_'.
    $query = 'SHOW TABLES LIKE "cache\_%"';
    return $this->connection->query($query, \PDO::FETCH_NUM)->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * Truncates the given table.
   *
   * @param string $table
   *   The name of the table to truncate.
   */
  protected function truncateTable(string $table): void {
    $query = "TRUNCATE TABLE `$table`";
    $statement = $this->connection->prepare($query);
    $statement->execute();
  }

}
