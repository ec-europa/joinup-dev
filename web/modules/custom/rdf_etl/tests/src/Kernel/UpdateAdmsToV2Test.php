<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_etl\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Tests the 'update_adms_to_v2' process step plugin.
 *
 * @group rdf_etl
 */
class UpdateAdmsToV2Test extends KernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * Testing graph.
   *
   * @var string
   */
  protected $graph = 'http://example.com/graph/sync_test';

  /**
   * The directory containing fixtures and assertions for each ADMS v2 change.
   *
   * @var string
   */
  protected $admsV2ChangelogDir;

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

    $this->admsV2ChangelogDir = realpath(__DIR__ . '/../../adms_v2_changelog');

    // Collect and import fixtures used for testing.
    foreach (file_scan_directory($this->admsV2ChangelogDir, '/\.rdf$/') as $file) {
      if ($data = $this->prepareRdfData($file->uri)) {
        $graph_store = new GraphStore($connection_uri);
        $graph = new Graph();
        $graph->parse($data);
        $out = $graph_store->replace($graph, $this->graph);
        if (!$out->isSuccessful()) {
          throw new \Exception("Cannot import file '{$file->uri}' in graph '{$this->graph}'.");
        }
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
    $plugin = $manager->createInstance('update_adms_to_v2', ['sync_graph' => $this->graph]);

    // Run updates.
    $plugin->execute([]);

    // Execute assertions.
    foreach (file_scan_directory($this->admsV2ChangelogDir, '/\.php$/') as $file) {
      require $file->uri;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->sparql->query("CLEAR GRAPH <{$this->graph}>;");
    parent::tearDown();
  }

  /**
   * Prepares the RDF data given a .rdf file.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return string|null
   *   The RDF data markup.
   */
  protected function prepareRdfData(string $uri): ?string {
    $content = trim(file_get_contents($uri));
    if ($content) {
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
$content
</rdf:RDF>
DATA;
    }
    return NULL;
  }

}
