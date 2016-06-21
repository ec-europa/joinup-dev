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

  /**
   * The data source name.
   *
   * @var string
   */
  protected $dsn;

  /**
   * The database connection username.
   *
   * @var string
   */
  protected $user;

  /**
   * The database connection password.
   *
   * @var string
   */
  protected $pass;

  /**
   * @param string $query
   * @return $this
   * @throws \BuildException
   */
  protected function execute($query) {
    $parts = [
      'echo ' . escapeshellarg($query),
      '|',
      escapeshellcmd($this->isqlPath),
      escapeshellarg($this->dsn),
      escapeshellarg($this->user),
      escapeshellarg($this->pass),
    ];
    $output = array();
    $return = NULL;
    exec(implode(' ', $parts), $output, $return);
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
    $this->execute('grant SPARQL_UPDATE to "SPARQL";')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO "SPARQL";')
      ->execute('GRANT execute ON SPARQL_INSERT_DICT_CONTENT TO SPARQL_UPDATE;')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO "SPARQL";')
      ->execute('GRANT execute ON DB.DBA.SPARQL_MODIFY_BY_DICT_CONTENTS TO SPARQL_UPDATE;');
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
   * Set data source name.
   *
   * @param string $dsn
   *    Data source name of the Virtuoso database.
   */
  public function setDataSourceName($dsn) {
    $this->dsn = $dsn;
  }

  /**
   * Set user name.
   *
   * @param string $user
   *    User name of the Virtuoso dba user.
   */
  public function setUsername($user) {
    $this->user = $user;
  }

  /**
   * Set password.
   *
   * @param string $pass
   *    Password of the Virtuoso dba user.
   */
  public function setPassword($pass) {
    $this->pass = $pass;
  }

}
