<?php

namespace Drupal\rdf_entity\Tests;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * A base class for the rdf tests.
 *
 * Sets up the SPARQL database connection.
 */
abstract class RdfKernelTestBase extends EntityKernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'ds',
    'comment',
    'field',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }
    // Test is not compatible with Virtuoso 6.
    if ($this->detectVirtuoso6()) {
      $this->markTestSkipped('Skipping: Not running on Virtuoso 6.');
    }

    $this->installModule('rdf_entity');
    $this->installModule('rdf_draft');
    $this->installConfig(['rdf_entity', 'rdf_draft']);
    $this->installEntitySchema('rdf_entity');
  }

}
