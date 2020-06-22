<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Sparql\Tasks\Sparql\loadTasks;

/**
 * Provides Joinup specific commands for Virtuoso backend.
 */
class VirtuosoCommands extends AbstractCommands {

  use loadTasks;

  /**
   * Imports RDF data from fixture files into Virtuoso storage.
   *
   * @param string[] $files
   *   A list of files to be imported. The file base name, without extension is
   *   used as graph URI host. For example the triples from path/to/content.rdf
   *   will be stored in the http://content graph.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @command virtuoso:import-fixtures
   */
  public function importFixtures(array $files): CollectionBuilder {
    $config = $this->getConfig();
    $endpointUrl = "http://{$config->get('sparql.host')}:{$config->get('sparql.port')}/sparql-graph-crud";
    $taskImport = $this->taskSparqlImportFromFile()->setEndpointUrl($endpointUrl);
    foreach ($files as $fileName) {
      $graphUri = 'http://' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));
      $taskImport->addTriples($graphUri, $fileName);
    }
    return $this->collectionBuilder()->addTask($taskImport);
  }

  /**
   * Sets a checkpoint with a given interval.
   *
   * @param int $interval
   *   The checkpoint interval.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @command virtuoso:checkpoint-set
   *
   * @todo Convert this to a dynamic command, once
   *   https://github.com/openeuropa/task-runner/issues/138 gets fixed.
   *
   * @see https://github.com/openeuropa/task-runner/issues/138
   */
  public function setCheckpoint(int $interval = 60): CollectionBuilder {
    $config = $this->getConfig();
    $task = $this->taskExec("echo 'checkpoint_interval({$interval});' | {$config->get('isql.bin')} {$config->get('sparql.host')} {$config->get('sparql.user')} {$config->get('sparql.password')}");
    return $this->collectionBuilder()->addTask($task);
  }

}
