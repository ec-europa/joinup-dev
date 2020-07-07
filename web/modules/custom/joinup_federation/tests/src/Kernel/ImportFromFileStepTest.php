<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

/**
 * Tests the 'file_import' pipeline step plugin.
 *
 * @group joinup_federation
 */
class ImportFromFileStepTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getUsedStepPlugins(): array {
    return ['file_import' => []];
  }

  /**
   * Tests misconfigured pipeline.
   */
  public function testMisconfiguredPipeline(): void {
    $this->expectExceptionObject(new \Exception("Step 'file_import' called without configuring a file or URL."));
    $this->runPipelineStep('file_import');
  }

  /**
   * Tests importing from a local file.
   */
  public function testImportFromFile(): void {
    $this->pipeline->setSteps([
      'file_import' => [
        'resource' => __DIR__ . '/../../fixtures/valid_adms.rdf',
      ],
    ]);
    $this->runPipelineStep('file_import');

    $graph_store = $this->createGraphStore();
    $triples = $graph_store->get('http://joinup-federation/sink')->toRdfPhp();

    // Check that triple were imported.
    Assert::assertArrayHasKey('http://asset', $triples);
    Assert::assertArrayHasKey('http://publisher', $triples);
    Assert::assertArrayHasKey('http://contact', $triples);
    Assert::assertArrayHasKey('http://vocabulary/term', $triples);

    $this->cleanUp();
  }

  /**
   * Tests importing from an URL.
   */
  public function testImportFromUrl(): void {
    // Mock the 'http_client' service.
    $http_client = $this->prophesize(ClientInterface::class);
    $response = new Response(200, [], file_get_contents(__DIR__ . '/../../fixtures/valid_adms.rdf'));
    $http_client->request('GET', 'http://example.com/endpoint', Argument::any())
      ->willReturn($response);
    $this->container->set('http_client', $http_client->reveal());

    $this->pipeline->setSteps([
      'file_import' => [
        'resource' => 'http://example.com/endpoint',
      ],
    ]);
    $this->runPipelineStep('file_import');

    $graph_store = $this->createGraphStore();
    $triples = $graph_store->get('http://joinup-federation/sink')->toRdfPhp();

    // Check that triple were imported.
    Assert::assertArrayHasKey('http://asset', $triples);
    Assert::assertArrayHasKey('http://publisher', $triples);
    Assert::assertArrayHasKey('http://contact', $triples);
    Assert::assertArrayHasKey('http://vocabulary/term', $triples);

    $this->cleanUp();

  }

  /**
   * Cleans-up testing data.
   */
  public function cleanUp(): void {
    $this->createGraphStore()->delete('http://joinup-federation/sink');
  }

}
