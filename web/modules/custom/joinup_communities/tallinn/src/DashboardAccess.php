<?php

declare(strict_types = 1);

namespace Drupal\tallinn;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
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
   * Creates a new service instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(StateInterface $state, OgAccessInterface $og_access) {
    $this->state = $state;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf(
      // Either the access is public.
      ($this->state->get('tallinn.dashboard.access_policy', 'restricted') === 'public') ||
      // Or the user has site-wide access permission.
      $account->hasPermission('administer tallinn settings') ||
      // Or the user has group access permission.
      $this->ogAccess->userAccess(Rdf::load(TALLINN_COMMUNITY_ID), 'administer tallinn settings')->isAllowed()
    );
  }

}
