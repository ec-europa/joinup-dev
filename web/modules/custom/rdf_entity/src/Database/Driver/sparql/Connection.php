<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\mysql\Connection.
 */

namespace Drupal\rdf_entity\Database\Driver\sparql;

/**
 * @addtogroup database
 * @{
 */

class Connection {
  public function query($query) {
    $sparql = new \EasyRdf_Sparql_Client($this->getQueryUri());
    $results = $sparql->query($query);
    return $results;

  }

  public function getQueryUri() {
    // @todo Read this from the settings file.
    return 'http://localhost:8890/sparql';
  }
}

/**
 * @} End of "addtogroup database".
 */
