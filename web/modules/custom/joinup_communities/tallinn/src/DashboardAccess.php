<?php

declare(strict_types = 1);

namespace Drupal\tallinn;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Dashboard access service.
 */
class DashboardAccess implements DashboardAccessInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Creates a new service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OgAccessInterface $og_access) {
    $this->configFactory = $config_factory;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $access_type = $this->configFactory->get('tallinn.settings')->get('dashboard.access_type');

    return AccessResult::allowedIf(
      // Either the access is public.
      ($access_type === 'public') ||
      // Or the user has site-wide access permission.
      $account->hasPermission('administer tallinn settings') ||
      // Or the user has group access permission.
      $this->ogAccess->userAccess(Rdf::load(TALLINN_COMMUNITY_ID), 'administer tallinn settings')->isAllowed()
    );
  }

}
