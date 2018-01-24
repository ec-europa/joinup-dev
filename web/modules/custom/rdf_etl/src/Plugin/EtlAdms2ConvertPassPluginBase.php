<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a base class for Adms2ConvertPass plugins.
 */
abstract class EtlAdms2ConvertPassPluginBase extends PluginBase implements EtlAdms2ConvertPassInterface {

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sparql = Database::getConnection('default', 'sparql_default');
  }

  /**
   * {@inheritdoc}
   */
  public function convert(): void {}

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {}

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return NULL;
  }

}
