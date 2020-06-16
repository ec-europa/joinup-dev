<?php

declare(strict_types = 1);

namespace Joinup\Tasks\Virtuoso;

/**
 * Provides loaders for Virtuoso Robo tasks.
 */
trait loadTasks {

  /**
   * Provides a tasks loader for Virtuoso/Query task.
   *
   * @return \Joinup\Tasks\Virtuoso\SparqlQuery|\Robo\Collection\CollectionBuilder
   *   The task object.
   */
  public function taskVirtuosoQuery() {
    return $this->task(SparqlQuery::class);
  }

  /**
   * Provides a tasks loader for Virtuoso/Import task.
   *
   * @return \Joinup\Tasks\Virtuoso\Import|\Robo\Collection\CollectionBuilder
   *   The task object.
   */
  public function taskVirtuosoImport() {
    return $this->task(Import::class);
  }

}
