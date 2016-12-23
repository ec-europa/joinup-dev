<?php

namespace Drupal\Tests\joinup_core;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf_entity\RdfDatabaseConnectionTrait;

/**
 * Base setup for a Joinup workflow test.
 *
 * @group rdf_entity
 */
class JoinupWorkflowTestBase extends BrowserTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * The og membership access manager service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * The entity access manager service.
   *
   * @var \Drupal\rdf_entity\RdfAccessControlHandler
   */
  protected $entityAccess;

  /**
   * The user provider service for the workflow guards.
   *
   * @var \Drupal\joinup_user\WorkflowUserProvider
   */
  protected $userProvider;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // The SPARQL connection has to be set up before.
    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }
    // Test is not compatible with Virtuoso 6.
    if ($this->detectVirtuoso6()) {
      $this->markTestSkipped('Skipping: Not running on Virtuoso 6.');
    }

    parent::setUp();
    $this->ogMembershipManager = \Drupal::service('og.membership_manager');
    $this->ogAccess = $this->container->get('og.access');
    $this->entityAccess = $this->container->get('entity_type.manager')->getAccessControlHandler('rdf_entity');
    $this->userProvider = $this->container->get('joinup_user.workflow.user_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete all data produced by testing module.
    foreach (['published', 'draft'] as $graph) {
      $query = <<<EndOfQuery
DELETE {
  GRAPH <http://example.com/dummy/$graph> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <http://example.com/dummy/$graph> {
    ?entity ?field ?value
  }
}
EndOfQuery;
      $this->sparql->query($query);
    }

    parent::tearDown();
  }

}
