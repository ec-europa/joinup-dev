<?php

namespace Drupal\Tests\joinup_core;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group rdf_entity
 */
class JoinupWorkflowTestBase extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->ogAccess = $this->container->get('og.access');
  }

}
