<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;

/**
 * Form to invite a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends GroupFormBase {

  /**
   * The status value for an invitation that has been accepted.
   *
   * @var string
   */
  const STATUS_MEMBERSHIP_PENDING = 'membership_pending';

  /**
   * The severity of the messages displayed to the user, keyed by result type.
   *
   * @var string[]
   */
  const INVITATION_MESSAGE_TYPES = [
    self::RESULT_SUCCESS => 'status',
    self::RESULT_FAILED => 'error',
    self::RESULT_RESENT => 'status',
    self::RESULT_ACCEPTED => 'status',
    self::RESULT_REJECTED => 'status',
    self::STATUS_MEMBERSHIP_PENDING => 'error',
  ];

  /**
   * The message templates to use for the notification mail.
   *
   * @var string
   */
  const TEMPLATES = [
    'collection' => 'collection_membership_invitation',
    'solution' => 'solution_membership_invitation',
  ];

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
  protected function getTemplateId(): string {
    return self::TEMPLATES[$this->entity->bundle()];
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

    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->entity->id(),
    ]);

    parent::submitForm($form, $form_state);
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
      ->set('field_invitation_og_role', $this->role)
      ->save();

    return $invitation;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInvitationType(): string {
    return 'group_membership';
  }

  /**
   * {@inheritdoc}
   */
  protected function processUser(UserInterface $user): string {
    $membership = $this->ogMembershipManager->getMembership($this->entity, $user->id(), OgMembershipInterface::ALL_STATES);
    // If a pending membership exists, then do not do anything.
    if (!empty($membership) && $membership->getState() === OgMembershipInterface::STATE_PENDING) {
      return self::STATUS_MEMBERSHIP_PENDING;
    }

    return parent::processUser($user);
  }

  /**
   * {@inheritdoc}
   */
  protected function processResults(array $results): void {
    foreach (array_filter($results) as $result => $count) {
      $type = self::INVITATION_MESSAGE_TYPES[$result];
      $args = [':count' => $count];
      switch ($result) {
        case self::RESULT_SUCCESS:
          $message = $this->formatPlural($count, '1 user has been invited to this group.', ':count users have been invited to this group.', $args);
          break;

        case self::RESULT_FAILED:
          $message = $this->formatPlural($count, 'The invitation could not be sent to 1 user. Please try again later.', 'The invitation could not be sent for :count users. Please try again later.', $args);
          break;

        case self::RESULT_RESENT:
          $message = $this->formatPlural($count, "The invitation was resent to 1 user who was already invited previously but hasn't yet accepted the invitation.", "The invitation was resent to :count users that were already invited previously but haven't yet accepted the invitation.", $args);
          break;

        case self::RESULT_ACCEPTED:
          $message = $this->formatPlural($count, '1 user was already subscribed to the group. No new invitation was sent.', ':count users were already subscribed to the group. No new invitation was sent.', $args);
          break;

        case self::RESULT_REJECTED:
          $message = $this->formatPlural($count, '1 user has previously rejected the invitation. No new invitation was sent.', ':count users have previously rejected the invitation. No new invitation was sent.', $args);
          break;

        case self::STATUS_MEMBERSHIP_PENDING:
          $message = $this->formatPlural($count, '1 user has a pending membership. Please, approve their membership request and assign the roles.', ':count users have a pending membership. Please, approve their membership requests and assign the roles.', $args);
          break;

        default:
          throw new \Exception("Unknown result type '$result'.");
      }
      $this->messenger()->addMessage($message, $type);
    }
  }

}
