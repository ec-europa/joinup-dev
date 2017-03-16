<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpSparql();
    $this->installModule('rdf_entity');
    $this->installModule('rdf_draft');
    $this->installConfig(['rdf_entity', 'rdf_draft']);
    $this->installEntitySchema('rdf_entity');
  }

}
