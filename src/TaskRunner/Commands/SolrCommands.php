<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use Psr\Http\Message\ResponseInterface;
use Robo\Exception\AbortTasksException;

/**
 * Provides commands for ApacheSolr admin.
 */
class SolrCommands extends AbstractCommands {

  /**
   * Maximum status execution time in seconds.
   *
   * @var int
   */
  const MAX_EXECUTION_TIME = 120;

  /**
   * Command map. Used to determine the right Solr command to execute.
   *
   * @var string[][]
   *   An associative array of command data, keyed by the operation (either
   *   'backup' or 'restore'. Each data array has two possible commands, keyed
   *   by a boolean value that indicates whether we are performing a status
   *   check or executing the actual command. This value will be TRUE if we are
   *   performing a status check, and FALSE otherwise.
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
   * The cached HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Takes a snapshot from the Solr server index.
   *
   * @param string $core
   *   The core name.
   *
   * @command solr:backup-core
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When the server response is invalid.
   */
  public function backupSolr(string $core): void {
    $this->say("Backup Solr '{$core}' core.");
    $this->executeReplicationCommand($core, 'backup');
    $this->say("Successfully backed-up Solr '{$core}' core.");
  }

  /**
   * Restores the Solr server index from a snapshot.
   *
   * @param string $core
   *   The core name.
   *
   * @command solr:restore-core
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When the server response is invalid.
   */
  public function restoreSolr(string $core): void {
    $this->say("Restoring Solr '{$core}' core.");
    $this->executeReplicationCommand($core, 'restore');
    $this->say("Successfully restored Solr '{$core}' core.");
  }

  /**
   * Executes a command: backup or restore.
   *
   * @param string $core
   *   The core name for which to build the URL.
   * @param string $operation
   *   The operation to be performed: 'restore' or 'backup'.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When the server response is invalid.
   */
  protected function executeReplicationCommand(string $core, string $operation): void {
    $this->httpGet($this->getUrl($core, $operation));

    // Solr core backup and restore are asynchronous processes. In order to
    // consider the backup/restore done, we need to check the status of the last
    // process.
    $start = time();
    $attempt = 0;
    do {
      $status = $this->getOperationStatus($core, $operation, $attempt);

      // Status has timed-out.
      if (time() - $start > static::MAX_EXECUTION_TIME) {
        throw new AbortTasksException("Timed-out while getting the {$operation} status for the Solr '{$core}' core.");
      }

      if ($status === 'in progress') {
        // Wait 2 seconds before trying again.
        sleep(2);
      }
    } while ($status !== 'success');
  }

  /**
   * Gets the operation status.
   *
   * @param string $core
   *   The core name for which to build the URL.
   * @param string $operation
   *   The operation to be performed: 'restore' or 'backup'.
   * @param int $attempt
   *   The attempt number.
   *
   * @return string
   *   Could be: 'in progress', 'success', 'failed'.
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When the Solr server response is invalid.
   */
  protected function getOperationStatus(string $core, string $operation, int &$attempt): string {
    $attempt++;
    $this->say("Check if {$operation} is complete (attempt: #{$attempt})");

    $response = $this->httpGet($this->getUrl($core, $operation, TRUE));

    if (($content = Json::decode($response->getBody()->getContents())) === NULL) {
      throw new AbortTasksException("Invalid response from Solr server, while trying to get the {$operation} status of Solr '{$core}' core.");
    }

    if ($operation === 'backup') {
      $status = $content['details']['backup']['status'] ?? 'in progress';
    }
    elseif ($operation === 'restore') {
      $status = $content['restorestatus']['status'];
    }
    $status = strtolower($status);

    // Operation failed.
    if ($status === 'failed') {
      throw new AbortTasksException("Failed to {$operation}, with output:\n" . print_r($content, TRUE));
    }

    return $status;
  }

  /**
   * Builds the Solr replicate URL.
   *
   * @param string $core
   *   The core name for which to build the URL.
   * @param string $operation
   *   The operation to be performed: 'restore' or 'backup'.
   * @param bool $checkStatus
   *   (optional) If the command is checking the status of the operation.
   *   Defaults to FALSE.
   *
   * @return string
   *   The URL.
   *
   * @see https://lucene.apache.org/solr/guide/6_6/making-and-restoring-backups.html
   */
  protected function getUrl(string $core, string $operation, bool $checkStatus = FALSE): string {
    $config = $this->getConfig();
    $command = static::COMMAND_MAP[$operation][$checkStatus];
    return "{$config->get("env.SOLR_URL")}/{$config->get("env.SOLR_CORE")}/replication?command={$command}&name={$core}&location={$config->get('solr.snapshot_dir')}&wt=json&json.nl=map";
  }

  /**
   * Performs an HTTP get request.
   *
   * @param string $url
   *   The HTTP get request URL.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response
   *
   * @throws \Robo\Exception\AbortTasksException
   *   When the Solr server response is invalid.
   */
  protected function httpGet(string $url): ResponseInterface {
    if (!isset($this->httpClient)) {
      $this->httpClient = new Client();
    }

    try {
      $response = $this->httpClient->get($url);
    }
    catch (\Exception $exception) {
      throw new AbortTasksException($exception->getMessage(), 0, $exception);
    }
    if ($response->getStatusCode() != 200) {
      throw new AbortTasksException("Solr server returned HTTP code {$response->getStatusCode()}, while querying {$url}.");
    }

    return $response;
  }

}
