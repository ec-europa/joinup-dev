<?php
/**
 * @file
 * Contains \DrupalProject\build\Phing\SetVirtuosoSparqlPermissions.
 */

namespace DrupalProject\Phing;

require_once 'phing/Task.php';

/**
 * Class SetVirtuosoSparqlPermissions.
 */
class SetVirtuosoSparqlPermissions extends \Task {

  /**
   * The location of the isql binary.
   *
   * @var string
   */
  protected $isqlPath;

  protected $dbaPass;

  /**
   * @param string $query
   * @return $this
   * @throws \BuildException
   */
  protected function execute($query) {
    $command = "echo '" . $query . "' | " . $this->isqlPath . " Virtuoso dba " . $this->dbaPass;
    $output = array();
    $return = NULL;
    exec($command, $output, $return);
    if ($return != 0) {
      foreach ($output as $line) {
        $this->log($line, \Project::MSG_ERR);
      }
      throw new \BuildException("Setting the virtuoso Sparql UPDATE permissions exited with code $return");
    }
    else {
      foreach ($output as $line) {
        $this->log($line, \Project::MSG_INFO);
      }
    }
    
    return $this;
  }

  /**
   * Set the permissions of the '/sparql' endpoint to allow update queries.
   */
  public function main() {
    $this->execute('grant SPARQL_UPDATE to "SPARQL"')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO "SPARQL"')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO SPARQL_UPDATE')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO "SPARQL"')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO SPARQL_UPDATE');
  }

  /**
   * Set path to isql binary.
   *
   * @param string $path
   *    Path on the server.
   */
  public function setIsqlPath($path) {
    $this->isqlPath = $path;
  }

  /**
   * Set dba password.
   *
   * @param string $pass
   *    Password of the Virtuoso dba user.
   */
  public function setDbaPassword($pass) {
    $this->dbaPass = $pass;
  }

}
