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
class ImportRdfFixtures extends \Task {

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
    $this->log('Executing: ' . implode(' ', $parts), \Project::MSG_INFO);
    if ($return != 0) {
      foreach ($output as $line) {
        $this->log($line, \Project::MSG_ERR);
      }
      throw new \BuildException("Importing RDF data exited with code $return");
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
    $path_parts = explode('/', __DIR__);
    $base_dir = implode('/', array_slice($path_parts, 0, -2));
    $fixtures_path = $base_dir . '/resources/fixtures';
    // Reset the import table (Needed for re-import).
    $this->execute("delete from db.dba.load_list;");
    foreach(glob($fixtures_path . '/*.rdf') as $rdf_file_path) {
      $filename = array_pop(explode('/', $rdf_file_path));
      $file = str_replace('.rdf', '', $filename);
      $graph_name = 'http://' . strtolower($file);
      // Delete the graph first...
      $this->execute("SPARQL CLEAR GRAPH <$graph_name>;");
      // Schedule for import.
      $this->execute("ld_add('$rdf_file_path', '$graph_name');");
    }
    // Import.
    $this->execute("rdf_loader_run();");
    $this->execute("checkpoint;");
    $this->execute("commit WORK;");
    $this->execute("checkpoint;");
    $this->execute("SELECT * FROM DB.DBA.LOAD_LIST WHERE ll_error IS NOT NULL;");
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
