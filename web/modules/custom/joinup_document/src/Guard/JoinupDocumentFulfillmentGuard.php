<?php

namespace Drupal\joinup_document\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\Guard\NodeGuard;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\joinup_core\WorkflowUserProvider;
use Drupal\og\MembershipManagerInterface;

/**
 * Class JoinupDocumentFulfillmentGuard.
 */
class JoinupDocumentFulfillmentGuard extends NodeGuard {

  /**
   * Instantiates the JoinupDocumentFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_core\WorkflowUserProvider $workflowUserProvider
   *   The workflow user provider service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The documents relation service.
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, WorkflowUserProvider $workflowUserProvider, JoinupRelationManager $relationManager, MembershipManagerInterface $ogMembershipManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    parent::__construct($entityTypeManager, $workflowUserProvider, $relationManager, $ogMembershipManager, $configFactory, $currentUser);
    $this->transitions = $this->configFactory->get('joinup_document.settings')->get('transitions');
  }

}
