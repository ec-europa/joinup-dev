<?php
/**
 * @file
 * Contains \DrupalProject\build\Phing\SetVirtuosoSparqlPermissions.
 */

namespace DrupalProject\Phing;

/**
 * Class SetVirtuosoSparqlPermissions.
 */
class ImportRdfFixtures extends VirtuosoTaskBase {

  /**
   * A directory, mounted on both the deployment machine and the db server.
   *
   * @var string
   */
  protected $sharedDirectory;

  /**
   * Set the permissions of the '/sparql' endpoint to allow update queries.
   */
  public function main() {
    $path_parts = explode('/', __DIR__);
    $base_dir = implode('/', array_slice($path_parts, 0, -2));
    $fixtures_path = $base_dir . '/resources/fixtures/';

    // When shared directory is in use, override fixture path.
    if (!empty($this->sharedDirectory)) {
      if (substr($this->sharedDirectory, -1) !== '/') {
        $this->sharedDirectory .= '/';
      }
      // When the database server and the web server are two distinct machines,
      // we need to proxy the rdf files from one machine to the other, by using a
      // shared mount.
      // Clean directory from previous deploy.
      if (is_dir($this->sharedDirectory)) {
        array_map('unlink', glob($this->sharedDirectory . '*'));
        rmdir($this->sharedDirectory);
      }

      $dir = opendir($fixtures_path);
      mkdir($this->sharedDirectory, 0777, TRUE);
      while (($file = readdir($dir)) !== FALSE) {
        if (
          ($file === '.') ||
          ($file === '..') ||
          (is_dir($fixtures_path . $file))
        ) {
          continue;
        }
        copy($fixtures_path . $file, $this->sharedDirectory . $file);
      }
      closedir($dir);
      $fixtures_path = $this->sharedDirectory;
    }

    foreach (glob($fixtures_path . '*.rdf') as $rdf_file_path) {
      $filename = array_pop(explode('/', $rdf_file_path));
      $file = str_replace('.rdf', '', $filename);
      $graph_name = 'http://' . strtolower($file);
      // Delete the graph first...
      $this->execute("SPARQL CLEAR GRAPH <$graph_name>;");
      exec("curl --digest --user $this->user:$this->pass --verbose --url 'http://$this->dsn:8890/sparql-graph-crud-auth?graph-uri=$graph_name' -T $rdf_file_path");
    }
  }

  public function setSharedDirectory($dir) {
    $this->sharedDirectory = $dir;
  }

}
