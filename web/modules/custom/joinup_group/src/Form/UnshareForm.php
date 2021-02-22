<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to unshare a community content from within groups.
 */
abstract class UnshareForm extends ShareFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'unshare_content_form';
  }

  /**
   * Form builder for the unshare form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity being unshared.
   *
   * @return array
   *   The form structure.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, ?EntityInterface $entity = NULL): array {
    $this->entity = $entity;

    $options = array_map(function ($group) {
      /** @var \Drupal\rdf_entity\RdfInterface $group */
      return $group->label();
    }, $this->getGroups());

    $form['groups'] = [
      '#type' => 'checkboxes',
      '#title' => 'Groups',
      '#options' => $options,
      '#default_value' => [],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $groups = [];
    // We can safely loop through these ids, as invalid options are handled
    // already by Drupal.
    foreach ($form_state->getValue('groups') as $id => $checked) {
      if ($checked) {
        $group = $this->sparqlStorage->load($id);
        $this->removeFromGroup($group);
        $groups[] = $group->label();
      }
    }

    if (!empty($groups)) {
      $this->messenger->addStatus('Item was unshared from the following groups: ' . implode(', ', $groups) . '.');
    }
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

  /**
   * Gets a list of groups where the content can be unshared from the user.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of groups where the user is a facilitator and the content is
   *   shared.
   */
  protected function getGroups(): array {
    $groups = $this->getAlreadySharedGroupIds();
    if (empty($groups)) {
      return $groups;
    }

    if ($this->currentUser->hasPermission('administer shared entities')) {
      return $this->sparqlStorage->loadMultiple($groups);
    }
    return array_intersect_key($this->getUserGroupsByPermission($this->getPermissionForAction('unshare')), array_flip($groups));
  }

  /**
   * Removes the current node from being shared inside a group.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to remove the node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the unshared entity cannot be saved after the group is
   *   removed from it.
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   *   Thrown when the entity storage is read only.
   */
  protected function removeFromGroup(RdfInterface $group): void {
    // Flipping is needed to easily unset the value.
    $current_ids = array_flip($this->getAlreadySharedGroupIds());
    unset($current_ids[$group->id()]);
    $this->entity->get($this->getSharedOnFieldName())->setValue(array_flip($current_ids));
    $this->entity->save();
  }

}
