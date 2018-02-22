<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

/**
 * Provides a Phing task that drops all the tables from a MySQL database.
 */
class MySqlEmptyDatabaseTask extends \PDOTask {

  /**
   * {@inheritdoc}
   */
  public function main(): void {
    $connection = $this->getConnection();
    $tables = $connection->query('SHOW TABLES;')->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
      $query = "DROP TABLE `$table`";
      $statement = $connection->prepare($query);
      $statement->execute();
    }
  }

}
