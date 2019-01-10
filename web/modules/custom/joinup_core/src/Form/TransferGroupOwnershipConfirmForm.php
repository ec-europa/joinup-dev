<?php

namespace Drupal\joinup_core\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for transferring the group ownership.
 */
class TransferGroupOwnershipConfirmForm extends ConfirmFormBase {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionPluginManager;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The user private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * The membership of the user that receives the ownership.
   *
   * @var \Drupal\og\OgMembershipInterface|null
   */
  protected $membership = NULL;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new confirmation form object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Action\ActionManager $action_plugin_manager
   *   The action plugin manager.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the entity storage of 'og_membership' is not found.
   */
  public function __construct(AccountInterface $current_user, RendererInterface $renderer, ActionManager $action_plugin_manager, MembershipManagerInterface $membership_manager, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->actionPluginManager = $action_plugin_manager;
    $this->membershipManager = $membership_manager;
    $this->tempStore = $temp_store_factory->get('joinup_transfer_group_ownership');
    $this->messenger = $messenger;

    if ($data = $this->tempStore->get($this->currentUser->id())) {
      if (!$this->membership = OgMembership::load($data['membership'])) {
        throw new \RuntimeException("Invalid membership with ID '{$data['membership']}'");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('plugin.manager.action'),
      $container->get('og.membership_manager'),
      $container->get('tempstore.private'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->membership->getGroup();
    return $this->t('Are you sure you want to transfer the ownership of %group @label to %user?', [
      '%group' => $group->label(),
      '@label' => $group->get('rid')->entity->getSingularLabel(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->membership->getGroup();

    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($this->membership->getGroup(), [
      'administrator',
    ]);

    $current_owners = array_map(function (OgMembershipInterface $membership) {
      if ($membership->getOwnerId() === $this->currentUser->id()) {
        return $this->t('@user (you)', ['@user' => $membership->getOwner()->getDisplayName()]);
      }
      return $membership->getOwner()->getDisplayName();
    }, $memberships);

    $args = [
      '%group' => $group->label(),
      '@label' => $group->get('rid')->entity->getSingularLabel(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
    ];

    $build = [];
    $build[] = [
      '#prefix' => '<h2>',
      '#markup' => $this->t('Warning!'),
      '#suffix' => '</h2>',
    ];
    if ($current_owners) {
      $build[] = [
        '#prefix' => '<p>',
        '#markup' => $this->formatPlural(count($current_owners),
          "The user %first_user will lose the ownership of the %group @label.",
          "The following users will lose the ownership of the %group @label:",
          $args + ['%first_user' => reset($current_owners)]
        ),
        '#suffix' => '</p>',
      ];
      if (count($current_owners) > 1) {
        $build[] = [
          '#theme' => 'item_list',
          '#items' => $current_owners,
        ];
      }
    }
    $build[] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The user %user will be the new owner of %group @label.', $args),
      '#suffix' => '</p>',
    ];
    $build[] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('This action cannot be undone.'),
      '#suffix' => '</p>',
    ];

    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Cleanup the bulk form selection.
    $this->tempStore->delete($this->currentUser->id());

    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->membership->getGroup();
    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($group, [
      'administrator',
    ]);
    // Revoke 'owner' role from existing group owners but grant them the
    // facilitator role, as a compensation, if missed.
    $facilitator = OgRole::loadByGroupAndName($group, 'facilitator');
    $bundle = $group->bundle();
    foreach ($memberships as $membership) {
      $membership->revokeRoleById("rdf_entity-$bundle-administrator");
      // Add the facilitator role, if missed.
      if (!$membership->hasRole($facilitator->id())) {
        $membership->addRole($facilitator);
      }
      $membership->save();
    }

    // Add the 'owner' role.
    $role = OgRole::loadByGroupAndName($group, 'administrator');
    $this->membership->addRole($role)->save();

    // Make this user also author of the group.
    $group->skip_notification = TRUE;
    $group->setOwner($this->membership->getOwner())->save();

    $former_owners = array_map(function (OgMembershipInterface $membership) {
      return $membership->getOwner()->getDisplayName();
    }, $memberships);

    $args = [
      '%group' => $group->label(),
      '@label' => $group->get('rid')->entity->getSingularLabel(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
      '@users' => implode(', ', $former_owners),
    ];
    if ($former_owners) {
      $message = $this->formatPlural(count($former_owners),
        'Ownership of %group @label transferred from user @users to %user.',
        'Ownership of %group @label transferred from users @users to %user.',
        $args
      );
    }
    else {
      $message = $this->t('Ownership of %group @label granted to %user.', $args);
    }
    $this->messenger->addStatus($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_transfer_group_ownership_confirm';
  }

  /**
   * Provide the access policy for the route that shows this form.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access() {
    // Probably we try to reuse a form that has been already used.
    if (!$this->membership) {
      return AccessResult::forbidden();
    }
    /** @var \Drupal\joinup_core\Plugin\Action\TransferGroupOwnershipAction $action */
    $action = $this->actionPluginManager->createInstance('joinup_transfer_group_ownership');
    return $action->access($this->membership, $this->currentUser, TRUE);
  }

}
