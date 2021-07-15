<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple form with a button to join or leave a collection.
 */
abstract class JoinGroupFormBase extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The group to join.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $group;

  /**
   * The current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'join_group_form';
  }

  /**
   * Constructs a JoinGroupFormBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, AccessManagerInterface $access_manager, MessengerInterface $messenger, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->accessManager = $access_manager;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('access_manager'),
      $container->get('messenger'),
      $container->get('form_builder')
    );
  }

  /**
   * Returns the label for the join submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the label.
   */
  public function getJoinSubmitLabel(): TranslatableMarkup {
    return $this->t('Join this :type', [
      ':type' => $this->group->bundle(),
    ]);
  }

  /**
   * Returns the label for the leave submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the label.
   */
  public function getLeaveSubmitLabel(): TranslatableMarkup {
    return $this->t('Leave this :type', [
      ':type' => $this->group->bundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AccountProxyInterface $user = NULL, ?RdfInterface $group = NULL): array {
    $form['#access'] = $this->access();
    $this->group = $group;
    $this->user = $this->loadUser((int) $user->id());

    // Inform anonymous users that they need to authenticate to join the group.
    if ($this->user->isAnonymous()) {
      $parameters = ['rdf_entity' => $this->group->id()];
      if ($this->accessManager->checkNamedRoute('joinup_group.authenticate_to_join', $parameters)) {
        $form['authenticate'] = [
          '#type' => 'link',
          '#title' => $this->getJoinSubmitLabel(),
          '#url' => Url::fromRoute('joinup_group.authenticate_to_join', $parameters),
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 'auto',
            ]),
          ],
        ];
        $form['#attached']['library'][] = 'core/drupal.ajax';
      }
    }

    // In case the user is not a member or does not have a pending membership,
    // give the possibility to request one.
    elseif (empty($membership = $this->getUserNonBlockedMembership())) {
      $form['join'] = [
        '#type' => 'submit',
        '#value' => $this->getJoinSubmitLabel(),
      ];
    }

    // If the user is already a member of the group, show a link to the
    // membership cancellation confirmation form, disguised as a form submit
    // button. The confirmation form should open in a modal dialog for
    // JavaScript-enabled browsers.
    elseif ($membership->getState() === OgMembershipInterface::STATE_ACTIVE) {
      $parameters = ['rdf_entity' => $this->group->id()];
      $form['leave'] = [
        '#theme' => 'group_leave_button',
      ];
      if ($this->accessManager->checkNamedRoute('joinup_group.leave_confirm_form', $parameters)) {
        $form['leave']['#confirm'] = [
          '#type' => 'link',
          '#title' => $this->getLeaveSubmitLabel(),
          '#url' => Url::fromRoute('joinup_group.leave_confirm_form', $parameters),
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 'auto']),
          ],
        ];
      }
      else {
        $form['leave']['#confirm'] = [
          '#markup' => $this->t('You cannot leave the %label :type', [
            '%label' => $this->group->label(),
            ':type' => $this->group->bundle(),
          ]),
        ];
      }
      $form['#attached']['library'][] = 'core/drupal.ajax';
    }

    // If the user has a pending membership, do not allow to request a new one.
    elseif ($membership->getState() === OgMembershipInterface::STATE_PENDING) {
      $form['pending'] = [
        '#type' => 'link',
        '#title' => $this->t('Membership is pending'),
        '#url' => Url::fromRoute('<current>', [], ['fragment' => 'pending']),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Only authenticated users can join a group.
    if ($this->user->isAnonymous()) {
      $form_state->setErrorByName('user', $this->t('<a href=":login">Sign in</a> or <a href=":register">register</a> to change your group membership.', [
        ':login' => Url::fromRoute('user.login')->toString(),
        ':register' => Url::fromRoute('user.register')->toString(),
      ]));
    }

    $membership = $this->getUserNonBlockedMembership();
    // Make sure the user is not already a member.
    if (!empty($membership)) {
      $form_state->setErrorByName('group', $this->t('You already are a member of this group.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $og_roles = [$this->loadOgRole($this->group->getEntityTypeId() . '-' . $this->group->bundle() . '-' . OgRoleInterface::AUTHENTICATED)];

    // Take into account the `field_ar_closed` in case of a collection.
    // @todo Collection specific code does not belong in the generic base class.
    //   This should be moved to the `JoinCollectionForm` which extends this.
    $state = $this->group instanceof CollectionInterface && $this->group->isClosed() ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;

    $membership = $this->createMembership($state, $og_roles);
    $membership->save();

    $this->messenger->addStatus($this->group->getNewMembershipSuccessMessage($membership));
  }

  /**
   * Creates a membership for the group and the user.
   *
   * @param string $state
   *   The membership state.
   * @param array $roles
   *   The role ids to pass in the membership.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The created membership.
   */
  protected function createMembership(string $state, array $roles): OgMembershipInterface {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entityTypeManager->getStorage('og_membership')->create([
      'type' => OgMembershipInterface::TYPE_DEFAULT,
    ]);
    $membership
      ->setOwner($this->user)
      ->setGroup($this->group)
      ->setState($state)
      ->setRoles($roles);

    return $membership;
  }

  /**
   * Access check for the form.
   *
   * @return bool
   *   True if the form can be accessed, false otherwise.
   */
  public function access(): bool {
    return $this->getRouteMatch()->getRouteName() !== 'joinup_group.leave_confirm_form';
  }

  /**
   * Returns a membership of the user that is active or pending.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The membership of the user or null.
   */
  protected function getUserNonBlockedMembership(): ?OgMembershipInterface {
    return $this->membershipManager->getMembership($this->group, $this->user->id(), [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
    ]);
  }

  /**
   * Loads the user with the given ID.
   *
   * @param int $user_id
   *   The user ID.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function loadUser(int $user_id): UserInterface {
    return $this->entityTypeManager->getStorage('user')->load($user_id);
  }

  /**
   * Loads the OG role with the given ID.
   *
   * @param string $role_id
   *   The OG role ID.
   *
   * @return \Drupal\og\OgRoleInterface
   *   The OG role entity.
   */
  protected function loadOgRole(string $role_id): OgRoleInterface {
    return $this->entityTypeManager->getStorage('og_role')->load($role_id);
  }

}
