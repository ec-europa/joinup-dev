<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends InviteFormBase {

  /**
   * The message template to use for the notification mail.
   *
   * @var string
   */
  const TEMPLATE_GROUP_INVITE = 'discussion_invite';

  /**
   * The OG role to assign if the invitation is accepted.
   *
   * @var \Drupal\og\OgRoleInterface
   */
  protected $role;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * Constructs a new InviteToGroupForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitation_message_helper
   *   The invitation message helper service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, InvitationMessageHelperInterface $invitation_message_helper, MembershipManagerInterface $og_membership_manager) {
    parent::__construct($entityTypeManager, $invitation_message_helper);
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_invite.invitation_message_helper'),
      $container->get('og.membership_manager')
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
    return $this->t('Invite members');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelButtonUrl(): Url {
    return new Url('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateId(): string {
    return self::TEMPLATE_GROUP_INVITE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL) {
    $this->entity = $rdf_entity;
    $form = parent::build($form, $form_state);

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

    $form['actions']['add_members'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add members'),
      '#submit' => [$this, '::submitAddMembers'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role_id = implode('-', [
      $this->entity->getEntityTypeId(),
      $this->entity->bundle(),
      $form_state->getValue('role'),
    ]);
    $this->role = $this->entityTypeManager->getStorage('og_role')->load($role_id);

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit callback for the invitation submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitAddMembers(array &$form, FormStateInterface $form_state): void {
    $users = $this->getUserList($form_state);
    $role_id = implode('-', [
      $this->entity->getEntityTypeId(),
      $this->entity->bundle(),
      $form_state->getValue('role'),
    ]);
    $role = $this->entityTypeManager->getStorage('og_role')->load($role_id);

    foreach ($users as $user) {
      $membership = $this->ogMembershipManager->getMembership($this->entity, $user->id());
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($this->entity, $user);
      }
      $membership->addRole($role);
      $membership->save();
    }

    $this->messenger()->addMessage($this->t('Successfully added the role %role to the selected users.', [
      '%role' => $role->label(),
    ]));
    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function createInvitation(UserInterface $user): InvitationInterface {
    /** @var \Drupal\joinup_invite\Entity\InvitationInterface $invitation */
    $invitation = $this->entityTypeManager->getStorage('invitation')->create([
      'bundle' => $this->getInvitationType(),
    ]);
    $invitation
      ->setRecipient($user)
      ->setEntity($this->entity)
      ->setExtraData(['role_id' => $this->role->id()])
      ->save();

    return $invitation;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInvitationType(): string {
    return 'group_membership';
  }

}
