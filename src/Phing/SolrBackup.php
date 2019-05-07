<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;

/**
 * Provides status check for Solr replication backup or restore.
 *
 * @see https://lucene.apache.org/solr/guide/7_7/making-and-restoring-backups.html
 */
class SolrBackup extends \Task {

  /**
   * Maximum status execution time in seconds.
   *
   * @var int
   */
  const MAX_EXECUTION_TIME = 120;

  /**
   * Command map.
   *
   * @var string[][]
   */
  const COMMAND_MAP = [
    'backup' => [
      FALSE => 'backup',
      TRUE => 'details',
    ],
    'restore' => [
      FALSE => 'restore',
      TRUE => 'restorestatus',
    ],
  ];

  /**
   * The Solr core.
   *
   * @var string
   */
  protected $core;

  /**
   * The operation: 'backup' or 'restore'.
   *
   * @var string
   */
  protected $operation;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Counter of 'get status' attempts.
   *
   * @var int
   */
  protected $attempt;

  /**
   * Constructs a new task instance.
   */
  public function __construct() {
    $this->httpClient = new Client();
  }

  /**
   * {@inheritdoc}
   */
  public function main():void {
    $this->log("Executing {$this->operation} on Solr '{$this->core}' core.");
    $this->executeCommand()->waitToComplete();
    $this->log("Successfully executed {$this->operation} on Solr '{$this->core}' core.");
  }

  /**
   * Executes a command: backup or restore.
   *
   * @return $this
   *
   * @throws \BuildException
   *   When the server response is invalid.
   */
  protected function executeCommand(): self {
    try {
      $response = $this->httpClient->get($this->getUrl());
    }
    catch (\Exception $exception) {
      throw new \BuildException($exception->getMessage(), $exception);
    }
    if ($response->getStatusCode() != 200) {
      throw new \BuildException("Solr server returned HTTP code {$response->getStatusCode()}, while trying to {$this->operation} Solr '{$this->core}' core.");
    }
    return $this;
  }

  /**
   * Checks the status until receives the success signal.
   *
   * Solr core backup and restore are asynchronous processes. In order to
   * consider the backup/restore done, we need to check the status of the last
   * process.
   *
   * @return $this
   *
   * @throws \BuildException
   *   When the operation failed.
   * @throws \BuildTimeoutException
   *   When the status execution has timed-out.
   */
  protected function waitToComplete(): self {
    $start = time();
    $this->attempt = 0;

    do {
      $status = $this->getStatus();

      // Getting status has timed-out.
      if (time() - $start > static::MAX_EXECUTION_TIME) {
        throw new \BuildTimeoutException("Timed-out while getting the {$this->operation} status for the Solr '{$this->core}' core.");
      }

      if ($status === 'in progress') {
        // Wait 2 seconds before trying again.
        sleep(2);
      }
    } while ($status !== 'success');

    return $this;
  }

  /**
   * Gets the operation status.
   *
   * @return string
   *   Could be: 'in progress', 'success', 'failed'.
   *
   * @throws \BuildException
   *   If an invalid response has been received from the Solr server or the
   *   operation is invalid.
   */
  protected function getStatus(): string {
    $this->log(sprintf('Get %s status in progress. Attempt: #%d', $this->operation, ++$this->attempt));
    $response = $this->httpClient->get($this->getUrl(TRUE));

    if (($content = Json::decode($response->getBody()->getContents())) === NULL) {
      throw new \BuildException("Invalid response from Solr server, while trying to get the {$this->operation} status of Solr '{$this->core}'core.");
    }

    $status = strtolower($this->operation === 'backup' ? $content['details']['backup']['status'] : $content['restorestatus']['status']);

    // Operation failed.
    if ($status === 'failed') {
      throw new \BuildException("Failed to {$this->operation}, with output:\n" . print_r($content, TRUE));
    }

    return $status;
  }

  /**
   * Builds the Solr replicate URL.
   *
   * @param bool $check_status
   *   (optional) If the command is checking the status of the operation.
   *   Defaults to FALSE.

   * @return string
   *   The URL.
   *
   * @see https://lucene.apache.org/solr/guide/7_7/making-and-restoring-backups.html
   */
  protected function getUrl(bool $check_status = FALSE): string {
    $properties = $this->getProject()->getProperties();
    $core_url = $properties["solr.core.{$this->core}.url"];
    $core_name = $properties["solr.core.{$this->core}.name"];
    return "{$core_url}/{$core_name}/replication?command={$this->getCommand($check_status)}&name={$this->core}&location={$properties['exports.solr.destination.folder']}&wt=json&json.nl=map";
  }

  /**
   * Returns the command to be performed.
   *
   * @param bool $check_status
   *   (optional) If the command is checking the status of the operation.
   *   Defaults to FALSE.
   *
   * @return string
   *   The command to be passed to 'command' query string item.
   */
  protected function getCommand(bool $check_status = FALSE): string {
    return static::COMMAND_MAP[$this->operation][$check_status];
  }

  /**
   * Sets the Solr core.
   *
   * @param string $core
   *   The Solr core.
   */
  public function setCore(string $core): void {
    if (empty($core)) {
      throw new \BuildException("The Solr core should be set.");
    }
    $this->core = $core;
  }

  /**
   * Sets the operation whose status is checked.
   *
   * @param string $operation
   *   The operation whose status is checked.
   *
   * @throws \InvalidArgumentException
   *   If the passed operation is not 'backup' or 'restore'.
   */
  public function setOperation(string $operation): void {
    if (!in_array($operation, ['backup', 'restore'], TRUE)) {
      throw new \BuildException("The operation should be 'backup' or 'restore', '$operation' given.");
    }
    $this->operation = $operation;
  }

}
