<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Joinup\Tasks\Virtuoso\loadTasks;
use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Exception\AbortTasksException;

/**
 * Provides commands for Virtuoso backend.
 */
class VirtuosoCommands extends AbstractCommands {

  use loadTasks;

  /**
   * Empties the Virtuoso backend.
   *
   * @param string[] $queries
   *   A space separated list of SPARQL queries to be executed against the
   *   Virtuoso endpoint.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @command virtuoso:query
   */
  public function query(array $queries): CollectionBuilder {
    $config = $this->getConfig();
    $endpointUrl = "http://{$config->get('sparql.user')}:{$config->get('sparql.password')}@{$config->get('sparql.host')}:{$config->get('sparql.port')}/sparql";
    $queryTask = $this->taskVirtuosoQuery()->setEndpointUrl($endpointUrl);

    foreach ($queries as $query) {
      $queryTask->query($query);
    }

    return $this->collectionBuilder()->addTask($queryTask);
  }

  /**
   * Validates the virtuoso:query command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data object.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   If no query arguments were provided.
   *
   * @hook validate virtuoso:query
   */
  public function validateQuery(CommandData $commandData): void {
    if (!$commandData->arguments()['queries']) {
      throw new AbortTasksException("No queries were provided as command arguments");
    }
  }

  /**
   * Empties the Virtuoso backend.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When an error occurred while getting the graph list.
   *
   * @command virtuoso:empty
   */
  public function empty(): CollectionBuilder {
    $config = $this->getConfig();
    $endpointUrl = "http://{$config->get('sparql.user')}:{$config->get('sparql.password')}@{$config->get('sparql.host')}:{$config->get('sparql.port')}/sparql";
    $queryTask = $this->taskVirtuosoQuery()->setEndpointUrl($endpointUrl);

    $query = 'SELECT DISTINCT(?g) WHERE { GRAPH ?g { ?s ?p ?o } } ORDER BY ?g';
    $result = $queryTask->query($query)->run();

    if (!$result->wasSuccessful()) {
      throw new AbortTasksException("Exit with: '{$result->getMessage()}'.");
    }

    $graphs = $result->getData()['result'][$query];

    if (!$graphs->count()) {
      $this->say("The Virtuoso backend is already empty.");
      return $this->collectionBuilder();
    }

    foreach ($graphs as $graph) {
      if ($graph_uri = $graph->g->getUri()) {
        $queryTask->query("CLEAR GRAPH <{$graph_uri}>;");
      }
    }

    return $this->collectionBuilder()->addTask($queryTask);
  }

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
  public function import(array $files): CollectionBuilder {
    $config = $this->getConfig();
    $endpointUrl = "http://{$config->get('sparql.host')}:{$config->get('sparql.port')}/sparql-graph-crud";
    $taskImport = $this->taskVirtuosoImport()->setEndpointUrl($endpointUrl);
    foreach ($files as $file) {
      $graphUri = 'http://' . strtolower(pathinfo($file, PATHINFO_FILENAME));
      $taskImport->triples($graphUri, file_get_contents($file));
    }
    return $this->collectionBuilder()->addTask($taskImport);
  }

  /**
   * Validates the virtuoso:import-fixtures command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data object.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   Either no arguments were provided or some of the provided files don't
   *   exist or are not readable.
   *
   * @hook validate virtuoso:import-fixtures
   */
  public function validateImport(CommandData $commandData): void {
    if (!$commandData->arguments()['files']) {
      throw new AbortTasksException("No files were provided as command arguments");
    }

    $invalidFiles = [];
    foreach ($commandData->arguments()['files'] as $file) {
      if (!file_exists($file) || !is_readable($file)) {
        $invalidFiles[] = $file;
      }
    }
    if ($invalidFiles) {
      throw new AbortTasksException("Files not exist or are not readable: '" . implode("', '", $invalidFiles) . "'");
    }
  }

}
