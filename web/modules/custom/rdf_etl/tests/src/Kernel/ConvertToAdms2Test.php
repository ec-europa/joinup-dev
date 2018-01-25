<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_etl\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassInterface;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Tests the 'convert_to_adms2' process step plugin.
 *
 * @group rdf_etl
 */
class ConvertToAdms2Test extends KernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * The  ADMS v1 to v2 transformation plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginManager
   */
  protected $adms2ConverPassPluginManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_entity',
    'rdf_etl',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();

    $options = $this->sparql->getConnectionOptions();
    $connection_uri = "http://{$options['host']}:{$options['port']}/sparql-graph-crud";

    $this->adms2ConverPassPluginManager = $this->container->get('plugin.manager.etl_adms2_convert_pass');
    $graph_uri = EtlAdms2ConvertPassInterface::TEST_GRAPH;

    $rdf_data = [];
    // Collect RDF testing data from plugins.
    foreach ($this->adms2ConverPassPluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassInterface $plugin */
      $plugin = $this->adms2ConverPassPluginManager->createInstance($plugin_id);
      if ($plugin_rdf_data = $plugin->getTestingRdfData()) {
        $rdf_data[] = $plugin_rdf_data;
      }
    }

    if ($rdf_data = $this->prepareRdfData(implode("\n", $rdf_data))) {
      $graph_store = new GraphStore($connection_uri);
      $graph = new Graph();
      $graph->parse($rdf_data);
      $out = $graph_store->replace($graph, $graph_uri);
      if (!$out->isSuccessful()) {
        throw new \Exception("Cannot import RDF data from plugin '$plugin_id' in graph '$graph_uri'.");
      }
    }
  }

  /**
   * Test ADMSv2 changes.
   */
  public function test() {
    /** @var \Drupal\rdf_etl\Plugin\EtlProcessStepManager $manager */
    $manager = $this->container->get('plugin.manager.etl_process_step');
    /** @var \Drupal\rdf_etl\Plugin\EtlProcessStepInterface $plugin */
    $convert_plugin = $manager->createInstance('convert_to_adms2', ['sync_graph' => EtlAdms2ConvertPassInterface::TEST_GRAPH]);

    // Run updates.
    $convert_plugin->execute([]);

    // Execute assertions.
    foreach ($this->adms2ConverPassPluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassInterface $plugin */
      $plugin = $this->adms2ConverPassPluginManager->createInstance($plugin_id);
      $plugin->performAssertions($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->sparql->query("CLEAR GRAPH <" . EtlAdms2ConvertPassInterface::TEST_GRAPH . ">;");
    parent::tearDown();
  }

  /**
   * Prepares the RDF data.
   *
   * @param string|null $data
   *   RDF data.
   *
   * @return string|null
   *   The RDF data markup.
   */
  protected function prepareRdfData(?string $data): ?string {
    if ($data && $data = trim($data)) {
      return <<<DATA
<?xml version="1.0" encoding="UTF-8" ?>
<rdf:RDF
    xmlns:adms="http://www.w3.org/ns/adms#"
    xmlns:admssw="http://purl.org/adms/sw/"
    xmlns:dcat="http://www.w3.org/ns/dcat#"
    xmlns:dct="http://purl.org/dc/terms/"
    xmlns:doap="http://usefulinc.com/ns/doap#"
    xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:qb="http://purl.org/linked-data/cube#"
    xmlns:rad="http://www.w3.org/ns/radion#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:schema="http://schema.org/"
    xmlns:skos="http://www.w3.org/2004/02/skos/core#"
    xmlns:spdx="http://spdx.org/rdf/terms#"
    xmlns:swid="http://standards.iso.org/iso/19770/-2/2009/"
    xmlns:trove="http://sourceforge.net/api/trove/index/rdf#"
    xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
    xmlns:wdrs="http://www.w3.org/2007/05/powder-s#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:dc="http://purl.org/dc/elements/1.1/">
$data
</rdf:RDF>
DATA;
    }
    return NULL;
  }

}
