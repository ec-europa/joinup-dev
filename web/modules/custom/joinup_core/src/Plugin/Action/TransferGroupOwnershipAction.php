<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action allowing an admin to transfer a group ownership.
 *
 * @Action(
 *   id = "joinup_transfer_group_ownership",
 *   label = @Translation("Transfer group ownership"),
 *   type = "og_membership",
 *   confirm_form_route_name = "joinup_core.transfer_group_ownership_confirm",
 * )
 */
class TransferGroupOwnershipAction extends ActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Messages collector in case of warnings or errors.
   *
   * @var array[]
   */
  protected $messages = [];

  /**
   * Constructs a new action object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = $temp_store_factory->get('joinup_transfer_group_ownership');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $memberships) {
    // We only allow one user to pickup the membership.
    if (count($memberships) > 1) {
      $membership = reset($memberships);
      $this->messages['warning'][] = $this->t('You cannot transfer the @label ownership to more than one user. Please select a single user.', [
        '@label' => $membership->getGroup()->get('rid')->entity->getSingularLabel(),
      ]);
    }
    parent::executeMultiple($memberships);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $membership->getGroup();

    // Is the selected member already the owner?
    if ($membership->hasRole("rdf_entity-{$group->bundle()}-administrator")) {
      $this->messages['warning'][] = $this->t('Member %member is already the owner of %group @label. Please select other user.', [
        '%member' => $membership->getOwner()->label(),
        '%group' => $group->label(),
        '@label' => $group->get('rid')->entity->getSingularLabel(),
      ]);
    }

    // After validating, store the data in the user private tempstore. We'll
    // retrieve the information later, in the confirm form submit callback or in
    // the redirect alter event subscriber.
    // @see \Drupal\joinup_core\Form\TransferGroupOwnershipConfirmForm
    // @see \Drupal\joinup_core\EventSubscriber\TransferGroupOwnershipSubscriber
    $this->tempStore->set($this->currentUser->id(), [
      'membership' => $membership->id(),
      'messages' => $this->messages,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($membership, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $membership->getGroup();
    $can_transfer_ownership = $this->canTransferOwnership($group, $account);
    return $return_as_object ? ($can_transfer_ownership ? AccessResult::allowed() : AccessResult::forbidden()) : $can_transfer_ownership;
  }

  /**
   * Checks if a user is able to transfer the ownership of a given group.
   *
   * The ownership transfer is allowed when the transfer is triggered either by
   * a user that has site-wide permissions for such operations or the user is
   * the current owner of the group.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group whom ownership is about to be transferred.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to be checked.
   *
   * @return bool
   *   If the user is allowed to transfer the ownership of the group.
   */
  public function canTransferOwnership(RdfInterface $group, AccountInterface $account): bool {
    // The user has proper site-wide permission for this operation.
    return $account->hasPermission("administer {$group->bundle()} ownership") ||
      // Or the user is the current group owner.
      $this->isGroupOwner($group, $account);
  }

  /**
   * Finds out if the given account is owner of the given group.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The use account.
   *
   * @return bool
   *   If the given account is owner of the given group.
   */
  protected function isGroupOwner(RdfInterface $group, AccountInterface $account): bool {
    $membership = Og::getMembership($group, $account);
    if ($membership) {
      return $membership->hasRole("rdf_entity-{$group->bundle()}-administrator");
    }
    return FALSE;
  }

}
