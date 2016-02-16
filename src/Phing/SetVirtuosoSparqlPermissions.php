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
   * Set the permissions of the '/sparql' endpoint to allow update queries.
   */
  public function main() {
    $command = "echo 'grant SPARQL_UPDATE to \"SPARQL\";' | " . $this->isqlPath . " 1111 dba " . $this->dbaPass;
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
