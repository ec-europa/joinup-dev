<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\joinup_collection\JoinupCollectionHelper;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgAccessInterface;
use Drupal\og\Plugin\Action\DeleteOgMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes a group membership.
 *
 * @Action(
 *   id = "joinup_og_membership_delete_action",
 *   label = @Translation("Delete the selected membership(s) from solution/collection"),
 *   type = "og_membership",
 *   confirm_form_route_name = "joinup_group.membership_delete_action.confirm",
 * )
 */
class DeleteGroupMembership extends DeleteOgMembership {

  /**
   * The user private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a new action plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory
   *   The private tempstore factory service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, OgAccessInterface $og_access, PrivateTempStoreFactory $private_temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $og_access);
    $this->privateTempStore = $private_temp_store_factory->get('joinup_group.og_membership_delete_action');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.access'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $memberships): void {
    $this->privateTempStore->set('memberships', $memberships);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?OgMembership $membership = NULL): void {
    $this->executeMultiple([$membership]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($membership, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\Entity\OgMembership $membership */
    // 'Joinup' collection membership cannot be revoked.
    if ($membership->getGroupId() === JoinupCollectionHelper::getCollectionId()) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }
    return parent::access($membership, $account, $return_as_object);
  }

}
