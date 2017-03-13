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
class VirtuosoTaskBase extends \Task {

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
   * A directory, mounted on both the deployment machine and the db server.
   *
   * @var string
   */
  protected $sharedDirectory;

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
    $this->log('Executing: ' . implode(' ', $parts), \Project::MSG_INFO);
    if ($return != 0) {
      foreach ($output as $line) {
        $this->log($line, \Project::MSG_ERR);
      }
      throw new \BuildException("An error occurred while executing the isql command $return");
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

    throw new \Exception('VirtuosoTaskBase should not be directly instantiated.');
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

  public function setSharedDirectory($dir) {
    $this->sharedDirectory = $dir;
  }

}
