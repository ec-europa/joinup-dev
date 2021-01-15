<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\og\OgMembershipInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
class AddToGroupForm extends GroupFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_to_group_form';
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
  protected function getTemplateId(): string {
    return '';
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

    $users = $this->getUserList($form_state);
    foreach ($users as $user) {
      $membership = $this->ogMembershipManager->getMembership($this->entity, $user->id(), OgMembershipInterface::ALL_STATES);
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($this->entity, $user);
      }
      // If the membership is pending or blocked, this should re-enable the
      // membership. In case it is already active, nothing will change.
      $membership->setState(OgMembershipInterface::STATE_ACTIVE);
      $membership->addRole($this->role);
      $membership->save();
    }

    $this->messenger()->addMessage($this->t('Successfully added the role %role to the selected users.', [
      '%role' => $this->role->label(),
    ]));
    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInvitationType(): string {
    return '';
  }

}
