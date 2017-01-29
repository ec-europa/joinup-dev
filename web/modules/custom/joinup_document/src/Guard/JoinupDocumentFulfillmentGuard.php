<?php

namespace Drupal\joinup_document\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_document\JoinupDocumentRelationManager;
use Drupal\joinup_user\WorkflowUserProvider;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\user\RoleInterface;

/**
 * Class JoinupDocumentFulfillmentGuard.
 */
class JoinupDocumentFulfillmentGuard implements GuardInterface {

  /**
   * Elibrary option defining that only facilitators can create content.
   */
  const ELIBRARY_ONLY_FACILITATORS = 0;

  /**
   * Elibrary option defining that members and facilitators can create content.
   */
  const ELIBRARY_MEMBERS_FACILITATORS = 1;

  /**
   * Elibrary option defining that any registered user can create content.
   */
  const ELIBRARY_REGISTERED_USERS = 2;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The documents relation manager.
   *
   * @var \Drupal\joinup_document\JoinupDocumentRelationManager
   */
  protected $relationManager;

  /**
   * The workflow user provider service.
   *
   * @var \Drupal\joinup_user\WorkflowUserProvider
   */
  protected $workflowUserProvider;

  /**
   * Instantiates the JoinupDocumentFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_user\WorkflowUserProvider $workflowUserProvider
   *   The workflow user provider service.
   * @param \Drupal\joinup_document\JoinupDocumentRelationManager $relationManager
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
