<?php

declare(strict_types = 1);

namespace Joinup\Tasks\Virtuoso;

use EasyRdf\Sparql\Client;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Provides a SPARQL query task against the Virtuoso backend.
 */
class SparqlQuery extends BaseTask {

  /**
   * Storage for the list of queries.
   *
   * @var array
   */
  protected $stack = [];

  /**
   * The Virtuoso endpoint URL.
   *
   * @var string
   */
  protected $endpointUrl;

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (empty($this->endpointUrl)) {
      return Result::error($this, "Endpoint URL not set");
    }
    if (!$this->stack) {
      return Result::error($this, 'No queries were passed.');
    }

    $client = new Client($this->endpointUrl);
    $data = [];
    foreach ($this->stack as $query) {
      $this->printTaskInfo("Querying Virtuoso: '{query}'", ['query' => $query]);
      try {
        $data['result'][$query] = $client->query($query);
      }
      catch (\Throwable $exception) {
        return Result::error($this, "Query failed with: '{$exception->getMessage()}'");
      }
    }

    // Cleanup the stack.
    $this->stack = [];

    return Result::success($this, 'Queries ran successfully', $data);
  }

  /**
   * Adds a new query.
   *
   * @param string $query
   *   The query to run against the backend.
   *
   * @return $this
   */
  public function query(string $query): self {
    $this->stack[] = $query;
    return $this;
  }

  /**
   * Sets the Virtuoso endpoint.
   *
   * @param string $endpointUrl
   *   The URL of the Virtuoso endpoint.
   *
   * @return $this
   */
  public function setEndpointUrl(string $endpointUrl): self {
    $this->endpointUrl = $endpointUrl;
    return $this;
  }

}
