<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Graph;

/**
 * Tests the 'convert_to_adms2' pipeline step plugin.
 *
 * @group pipeline
 */
class ConvertToAdms2Test extends KernelTestBase {

  use RdfDatabaseConnectionTrait;
  use RdfEntityGraphStoreTrait;

  /**
   * The  ADMS v1 to v2 transformation plugin manager.
   *
   * @var \Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginManager
   */
  protected $adms2ConverPassPluginManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'pipeline',
    'joinup_federation',
    'rdf_entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();

    $this->adms2ConverPassPluginManager = $this->container->get('plugin.manager.joinup_federation_adms2_convert_pass');
    $graph_uri = JoinupFederationAdms2ConvertPassInterface::TEST_GRAPH;

    $rdf_data = '';
    // Collect RDF testing data from plugins.
    foreach ($this->adms2ConverPassPluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\joinup_federation\JoinupFederationAdms2ConvertPassInterface $plugin */
      $plugin = $this->adms2ConverPassPluginManager->createInstance($plugin_id);
      if ($plugin_rdf_data = $plugin->getTestingRdfData()) {
        $rdf_data .= "$plugin_rdf_data\n";
      }
    }

    if ($rdf_data = $this->prepareRdfData($rdf_data)) {
      $graph = new Graph($graph_uri);
      $graph->parse($rdf_data);
      $this->createGraphStore()->replace($graph);
    }
  }

  /**
   * Test ADMSv2 changes.
   */
  public function test() {
    /** @var \Drupal\pipeline\Plugin\PipelineStepPluginManager $manager */
    $manager = $this->container->get('plugin.manager.pipeline_step');
    /** @var \Drupal\pipeline\Plugin\PipelineStepInterface $plugin */
    $convert_plugin = $manager->createInstance('convert_to_adms2', ['sink_graph' => JoinupFederationAdms2ConvertPassInterface::TEST_GRAPH]);

    // Run updates.
    $data = [];
    $convert_plugin->execute($data);

    // Execute assertions.
    foreach ($this->adms2ConverPassPluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\joinup_federation\JoinupFederationAdms2ConvertPassInterface $plugin */
      $plugin = $this->adms2ConverPassPluginManager->createInstance($plugin_id);
      $plugin->performAssertions($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->sparql->query("CLEAR GRAPH <" . JoinupFederationAdms2ConvertPassInterface::TEST_GRAPH . ">;");
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
