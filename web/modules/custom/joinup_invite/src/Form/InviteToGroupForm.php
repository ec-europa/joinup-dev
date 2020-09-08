<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\user\UserInterface;

/**
 * Form to invite a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends GroupFormBase {

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

    $this->messenger()->addMessage($this->t('Successfully invited the selected users.', [
      '%role' => $this->role->label(),
    ]));
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
