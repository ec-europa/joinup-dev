<?php

namespace Drupal\Tests\joinup_core;

use Drupal\Tests\BrowserTestBase;

/**
 * Base setup for a joinup workflow test.
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
    parent::setUp();
    $this->ogMembershipManager = \Drupal::service('og.membership_manager');
    $this->ogAccess = $this->container->get('og.access');
    $this->entityAccess = $this->container->get('entity_type.manager')->getAccessControlHandler('rdf_entity');
    $this->userProvider = $this->container->get('joinup_user.workflow.user_provider');
  }

}
