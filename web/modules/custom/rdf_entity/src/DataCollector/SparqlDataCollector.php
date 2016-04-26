<?php

namespace Drupal\rdf_entity\DataCollector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\webprofiler\DataCollector\DrupalDataCollectorTrait;
use Drupal\webprofiler\DrupalDataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class DatabaseDataCollector.
 */
class SparqlDataCollector extends DataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait;

  private $database;

  private $configFactory;

  /**
   * Setup DatabaseDataCollector.
   *
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response, \Exception $exception = NULL) {
    $queries = $this->database->getLogger()->get('webprofiler');

    foreach ($queries as &$query) {
      // Remove caller args.
      unset($query['caller']['args']);

      // Remove query args element if empty.
      if (empty($query['args'])) {
        unset($query['args']);
      }

      // Save time in milliseconds.
      $query['time'] = $query['time'] * 1000;
    }

    $query_sort = $this->configFactory->get('webprofiler.config')->get('query_sort');
    if ('duration' === $query_sort) {
      usort($queries, [
        "\\Drupal\\rdf_entity\\DataCollector\\SparqlDataCollector",
        "orderQueryByTime",
      ]);
    }

    $this->data['queries'] = $queries;

    $options = $this->database->getConnectionOptions();

    // Remove password for security.
    unset($options['password']);

    $this->data['database'] = $options;
  }

  /**
   * Get database connection options.
   *
   * @return array
   *    Database connection options.
   */
  public function getDatabase() {
    return $this->data['database'];
  }

  /**
   * Return amount of queries ran.
   *
   * @return int
   *    Number of queries.
   */
  public function getQueryCount() {
    return count($this->data['queries']);
  }

  /**
   * Get all executed rdf queries.
   *
   * @return array
   *   List of rdf queries.
   */
  public function getQueries() {
    return $this->data['queries'];
  }

  /**
   * Returns the total execution time.
   *
   * @return float
   *   Time
   */
  public function getTime() {
    $time = 0;

    foreach ($this->data['queries'] as $query) {
      $time += $query['time'];
    }

    return $time;
  }

  /**
   * Returns a color based on the number of executed queries.
   *
   * @return string
   *   Color
   */
  public function getColorCode() {
    $count = $this->getQueryCount();
    $time = $this->getTime();
    if ($count < 10 && $time < 40) {
      return 'green';
    }
    if ($count < 20 && $time < 60) {
      return 'yellow';
    }

    return 'red';
  }

  /**
   * Returns the configured query highlight threshold.
   *
   * @return int
   *   Threshold
   */
  public function getQueryHighlightThreshold() {
    // When a profile is loaded from storage this object is deserialized and
    // no constructor is called so we cannot use dependency injection.
    return \Drupal::config('webprofiler.config')->get('query_highlight');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'sparql';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Sparql');
  }

  /**
   * {@inheritdoc}
   */
  public function getPanelSummary() {
    return $this->t('Executed queries: @count', ['@count' => $this->getQueryCount()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon() {

    return 'iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAYAAAByDd+UAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wseCQolVpASpQAAAc5JREFUSMe91jtoFFEYBeAvG3VFEwOi4KvSwifGQu3tglgoBC3UWmIKX62VTWzs1N7CgBC1tbCIoMX6CGiCiDZpLFRiQrCIht21+QPDshnuuhcvXAbmPzNn/sc5c8m7qriGGSzjJ8YxiEpmLrvwGs1V9oWcpFU8KSFb2YO5CE/hewLheK4U92NrAm4oF+H6RFx/LsI5NBJwn7ohWYOduI/fCf1r4krxBb3Yjr04gN0YWKV8e3A7smriDZ5ioYSsFtMM+nAZUwXAAh7geGDW4mgQrWT0ElexMTA38B5LEa/jGyaiGoQY75Z82SxGcAfzce85LmFbmwocxGjgpnAC64qAMwm1X47rJM5iR0J/m3jULjiZQFjHLWxKHKi+eG6iNVAp9KhsVaJvi93qJ7uDpxDWEnCNkMNADtLTHQzNC5wrjninQ9OLz9hS0stZ3AxbGsJFHMEGfMWvFvwhDOMk/uBL4OqtUzWCd4Ws5kP4x1qEP1YQ9itcR39B+B8K8Ub8th63q0qn1jYWX93E206trRvzvvev5t3NGi1kW7anc+lwc6Km9+UiXErELeYi/IgfCbhn//uYeDj3QbhWQnYePbm9uRqjPx12OIeHkVkP/AVSbdRhDiH9kwAAAABJRU5ErkJggg==';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'webprofiler/database',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    $data = $this->data;
    $conn = Database::getConnection();
    foreach ($data['queries'] as &$query) {
      $explain = TRUE;
      $type = 'select';

      if (strpos($query['query'], 'INSERT') !== FALSE) {
        $explain = FALSE;
        $type = 'insert';
      }

      if (strpos($query['query'], 'UPDATE') !== FALSE) {
        $explain = FALSE;
        $type = 'update';
      }

      if (strpos($query['query'], 'CREATE') !== FALSE) {
        $explain = FALSE;
        $type = 'create';
      }

      if (strpos($query['query'], 'DELETE') !== FALSE) {
        $explain = FALSE;
        $type = 'delete';
      }

      $query['explain'] = $explain;
      $query['type'] = $type;

      $quoted = [];
      foreach ((array) $query['args'] as $key => $val) {
        $quoted[$key] = is_null($val) ? 'NULL' : $conn->quote($val);
      }

      $query['query_args'] = strtr($query['query'], $quoted);
    }

    $data['query_highlight_threshold'] = $this->getQueryHighlightThreshold();
    return $data;
  }

  /**
   * Sort callback. Sort queries by timing.
   *
   * @param array $a
   *   Query.
   * @param array $b
   *   Query.
   *
   * @return int
   *   Sort for usort.
   */
  private function orderQueryByTime($a, $b) {
    $at = $a['time'];
    $bt = $b['time'];

    if ($at == $bt) {
      return 0;
    }
    return ($at < $bt) ? 1 : -1;
  }

}
