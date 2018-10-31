<?php

declare(strict_types = 1);

namespace Drupal\tallinn;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Dashboard access service.
 */
class DashboardAccess implements DashboardAccessInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * Creates a new service instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership manager service.
   */
  public function __construct(StateInterface $state, OgAccessInterface $og_access, MembershipManagerInterface $og_membership_manager) {
    $this->state = $state;
    $this->ogAccess = $og_access;
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $access_policy = $this->state->get('tallinn.access_policy', 'restricted');
    $tallinn_collection = Rdf::load(TALLINN_COMMUNITY_ID);
    // Deny access if the Tallinn collection does not exist.
    if (empty($tallinn_collection)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf(
      // Either the access is public.
      $access_policy === 'public' ||
      // Or the access is limited to the collection members.
      ($access_policy === 'collection' && $this->ogMembershipManager->isMember($tallinn_collection, $account)) ||
      // Or the user has site-wide access permission.
      $account->hasPermission('administer tallinn settings') ||
      // Or the user has group access permission.
      $this->ogAccess->userAccess($tallinn_collection, 'administer tallinn settings')->isAllowed()
    );
  }

}
