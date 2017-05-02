<?php
/**
 * @file
 * Contains \DrupalProject\build\Phing\SetVirtuosoSparqlPermissions.
 */

namespace DrupalProject\Phing;

/**
 * Class SetVirtuosoSparqlPermissions.
 */
class SetVirtuosoSparqlPermissions extends VirtuosoTaskBase {

  /**
   * Set the permissions of the '/sparql' endpoint to allow update queries.
   */
  public function main() {
    $this->execute('grant SPARQL_UPDATE to "SPARQL";')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO "SPARQL";')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO SPARQL_UPDATE;')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO "SPARQL";')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO SPARQL_UPDATE;');
  }

}
