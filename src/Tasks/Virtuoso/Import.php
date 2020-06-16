<?php

declare(strict_types = 1);

namespace Joinup\Tasks\Virtuoso;

use EasyRdf\Graph;
use EasyRdf\GraphStore;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Provides task allowing to import triples in a Virtuoso storage.
 */
class Import extends BaseTask {

  /**
   * Storage for the list of graphs/triples.
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
      return Result::error($this, 'Endpoint URL not set.');
    }
    if (!$this->stack) {
      return Result::error($this, 'No import content was passed.');
    }

    $graphStore = new GraphStore($this->endpointUrl);
    foreach ($this->stack as $graphUri => $triples) {
      $triples = implode("\n", $triples);
      $this->printTaskInfo("Importing triples in '{uri}' graph", ['uri' => $graphUri]);
      try {
        $graph = new Graph($graphUri, $triples);
        $graphStore->replace($graph);
      }
      catch (\Throwable $exception) {
        return Result::error($this, "Triples import failed with: '{$exception->getMessage()}'");
      }
    }

    // Cleanup the stack.
    $this->stack = [];

    return Result::success($this, "Imported triples in '{uris}' graph", [
      'uris' => implode("', '", array_keys($this->stack)),
    ]);
  }

  /**
   * Adds a new triples to be imported.
   *
   * @param string $graphUri
   *   The URI of the graph where to store the triples.
   * @param string $triples
   *   A block of triples as string blob.
   *
   * @return $this
   */
  public function triples(string $graphUri, string $triples): self {
    $this->stack[$graphUri][] = $triples;
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
