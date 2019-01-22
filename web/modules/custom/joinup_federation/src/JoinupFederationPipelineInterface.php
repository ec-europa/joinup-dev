<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\pipeline\Plugin\PipelinePipelineInterface;

/**
 * Provide an interface for Joinup federation pipeline plugins.
 */
interface JoinupFederationPipelineInterface extends PipelinePipelineInterface {

  /**
   * Returns the uri of the collection that the pipeline is related to.
   *
   * @return string|null
   *   The uri of the collection.
   */
  public function getCollection(): ?string;

  /**
   * Returns the graph URI for a specific federation graph.
   *
   * @param string $graph_type
   *   The type of graph.
   *
   * @return string
   *   The graph URI.
   */
  public function getGraphUri(string $graph_type): string;

  /**
   * Clears the data from a given graph.
   *
   * @param string $graph_uri
   *   The URI of the graph to be emptied.
   *
   * @return $this
   */
  public function clearGraph(string $graph_uri): self;

  /**
   * Clears the data from the federation graphs.
   *
   * @return $this
   */
  public function clearGraphs(): self;

  /**
   * Locks the pipeline, preventing concurrent imports.
   *
   * If there's an ongoing import run by other user, this function will return
   * FALSE, informing the caller that is not able to acquire the lock. If
   * there's no other import than the current one, will acquire a lock and
   * will return TRUE, allowing the caller to prevent concurrent operations. If
   * there is an ongoing import previously locked by the same user, the lock
   * will be refreshed and the lock timeout is reset.
   *
   * @return bool
   *   If the lock has been successful.
   */
  public function lock(): bool;

  /**
   * Releases the pipeline lock.
   *
   * This will release the lock create by the current user. Passing TRUE as
   * argument, will release the lock regardless of lock ownership.
   *
   * @param bool $ignore_ownership
   *   (optional) If TRUE, this will release the lock regardless if the current
   *   user owns the lock. Defaults to FALSE.
   */
  public function lockRelease(bool $ignore_ownership = FALSE): void;

}
