<?php

declare(strict_types = 1);

namespace Drupal\collection;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\WorkflowStatePermissionInterface;
use Drupal\og\MembershipManagerInterface;

/**
 * Service for determining whether changing workflow states is permitted.
 *
 * Depending on the user role some workflow states are not available. For
 * example if a collection is in the 'validated' state a facilitator can only
 * change the state to 'proposed' or 'draft', while a moderator can change to
 * any state.
 *
 * @see collection.settings.yml
 */
class CollectionWorkflowStatePermission implements WorkflowStatePermissionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a CollectionWorkflowStatePermissions object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MembershipManagerInterface $membershipManager) {
    $this->configFactory = $configFactory;
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    $allowed_conditions = $this->configFactory->get('collection.settings')->get('transitions');

    if ($account->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return TRUE;
    }

    // Check if the user has one of the allowed system roles.
    $authorized_roles = isset($allowed_conditions[$to_state][$from_state]) ? $allowed_conditions[$to_state][$from_state] : [];
    if (array_intersect($authorized_roles, $account->getRoles())) {
      return TRUE;
    }

    // Check if the user has one of the allowed group roles.
    $membership = $this->membershipManager->getMembership($entity, $account->id());
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

}
