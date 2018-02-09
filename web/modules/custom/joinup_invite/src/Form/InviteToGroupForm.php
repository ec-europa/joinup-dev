<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends InviteFormBase {

  /**
   * The group where to invite users.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $rdfEntity;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The invitation message helper service.
   *
   * @var \Drupal\joinup_invite\InvitationMessageHelperInterface
   */
  protected $messageHelper;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new InviteToGroupForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $message_helper
   *   The invitation message helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MembershipManagerInterface $og_membership_manager, InvitationMessageHelperInterface $message_helper, AccountProxyInterface $account) {
    parent::__construct($entityTypeManager);
    $this->ogMembershipManager = $og_membership_manager;
    $this->messageHelper = $message_helper;
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('joinup_invite.invitation_message_helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_to_group_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitButtonText(): TranslatableMarkup {
    return $this->t('Add members');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelButtonUrl(): Url {
    return new Url('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->rdfEntity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $this->rdfEntity = $rdf_entity;

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#required' => TRUE,
      '#options' => [
        'member' => $this->t('Member'),
        'facilitator' => $this->t('Facilitator'),
      ],
      '#default_value' => 'member',
    ];

    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Ensure that users are not already invited or members of the group.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $users = $this->getUserList($form_state);
    $invalid_users = [];

    foreach ($users as $user) {
      $invitations = $this->entityTypeManager->getStorage('invitation')->loadByProperties([
        'entity_type' => $this->rdfEntity->getEntityTypeId(),
        'entity_id' => $this->rdfEntity->id(),
        'recipient_id' => $user->id(),
        'bundle' => 'group',
      ]);
      $invitation = reset($invitations);

      $membership_states = [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_BLOCKED,
        OgMembershipInterface::STATE_PENDING
      ];
      $membership = $this->ogMembershipManager->getMembership($this->rdfEntity, $user, $membership_states);

      if (!empty($invitation) || !empty($membership)) {
        $invalid_users[] = $user->getAccountName();
      }
    }

    if (!empty($invalid_users)) {
      $error = t('The following users are already invited or members of the :group: :users', [
        ':group' => $this->rdfEntity->bundle(),
        ':users' => implode(', ', $invalid_users),
      ]);
      $form_state->setError($form['users'], $error);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = $this->getUserList($form_state);
    $role_id = implode('-', [
      $this->rdfEntity->getEntityTypeId(),
      $this->rdfEntity->bundle(),
      $form_state->getValue('role'),
    ]);
    $role = $this->entityTypeManager->getStorage('og_role')->load($role_id);
    $role_argument = $form_state->getValue('role') === 'facilitator' ? 'facilitator' : 'a member';
    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    foreach ($users as $user) {
      if ($this->rdfEntity->bundle() === 'collection') {
        /** @var \Drupal\joinup_invite\Entity\InvitationInterface $invitation */
        $invitation = $this->entityTypeManager->getStorage('invitation')
          ->create(['bundle' => 'group'])
          ->setEntity($this->rdfEntity)
          ->setRecipient($user)
          ->setOwner($current_user)
          ->setStatus(InvitationInterface::STATUS_PENDING);
        $invitation->save();

        $membership = $this->ogMembershipManager->createMembership($this->rdfEntity, $user);
        $membership->addRole($role);
        $membership->setState(OgMembershipInterface::STATE_PENDING)->save();

        $arguments = $this->generateArguments($this->rdfEntity);
        $arguments += ['@invitation:target_role' => $role_argument];

        $message = $this->messageHelper->createMessage($invitation, 'group_invite', $arguments);
        $message->save();
        $this->messageHelper->sendMessage($invitation, 'group_invite');

        drupal_set_message($this->t('An invitation has been sent to the selected users. Their membership is pending.'));
      }
      // @todo: Remove this when the invitations for solutions are implemented.
      elseif ($this->rdfEntity->bundle() === 'solution') {
        $membership = $this->ogMembershipManager->getMembership($this->rdfEntity, $user);
        if (empty($membership)) {
          $membership = $this->ogMembershipManager->createMembership($this->rdfEntity, $user);
        }
        $membership->addRole($role);
        $membership->save();

        drupal_set_message($this->t('Successfully added the role %role to the selected users.', [
          '%role' => $role->label(),
        ]));
      }
    }

    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->rdfEntity->id(),
    ]);
  }

  /**
   * Returns the arguments for an invitation message.
   *
   * @todo This was copied from NotificationSubscriberBase::generateArguments()
   *   but we cannot call that code directly since it is contained in an
   *   abstract class. Remove this once ISAICP-4152 is in.
   *
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4152
   *
   * @param \Drupal\Core\Entity\EntityInterface $rdf_entity
   *   The group for which to generate the message arguments.
   *
   * @return array
   *   The message arguments.
   */
  protected function generateArguments(EntityInterface $rdf_entity) : array {
    $arguments = [];
    /** @var \Drupal\user\UserInterface $actor */
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $actor_first_name = !empty($actor->get('field_user_first_name')->first()->value) ? $actor->get('field_user_first_name')->first()->value : '';
    $actor_family_name = !empty($actor->get('field_user_family_name')->first()->value) ? $actor->get('field_user_family_name')->first()->value : '';

    $arguments['@entity:title'] = $rdf_entity->label();
    $arguments['@entity:bundle'] = $rdf_entity->bundle();
    $arguments['@entity:url'] = $rdf_entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    $arguments['@actor:field_user_first_name'] = $actor_first_name;
    $arguments['@actor:field_user_family_name'] = $actor_family_name;

    return $arguments;
  }

}
