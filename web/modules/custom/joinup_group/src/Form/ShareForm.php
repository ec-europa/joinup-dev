<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionInterface;

/**
 * Form to share a community content inside collections.
 */
abstract class ShareForm extends ShareFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'share_content_form';
  }

  /**
   * Form builder for the share form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity being shared.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the group reference is not populated.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, ?EntityInterface $entity = NULL): array {
    $this->entity = $entity;

    $form['share'] = [
      '#theme' => 'social_share',
      '#entity' => $this->entity,
    ];

    $groups = $this->getShareableGroups();
    foreach ($groups as $id => $group) {
      $bundle = $group->bundle();
      $form['groups'][$bundle][$id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['share-box__row'],
        ],
        'entity' => $this->rdfBuilder->view($group, 'compact'),
        'checkbox' => [
          '#type' => 'checkbox',
          '#title' => $group->label(),
          // Replicate the behaviour of the "checkboxes" element again.
          // @see \Drupal\Core\Render\Element\Checkboxes::processCheckboxes
          '#return_value' => $id,
          '#default_value' => FALSE,
          // Drop the extra "checkbox" key.
          '#parents' => ['groups', $id],
        ],
      ];
    }

    foreach (['collection', 'solution'] as $bundle) {
      if (!empty($form['groups'][$bundle])) {
        $form['groups'][$bundle] += [
          '#theme_wrappers' => ['fieldset'],
          '#title' => ucfirst(RdfEntityType::load($bundle)->getPluralLabel()),
          '#tree' => TRUE,
          '#access' => !empty($groups),
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#extra_suggestion' => 'light_blue',
      '#value' => empty($groups) ? $this->t('Close') : $this->t('Share'),
    ];

    if ($this->isModal() || $this->isAjaxForm()) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Keep only the checked entries.
    $groups = array_filter($form_state->getValue('groups'));
    $group_labels = [];
    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($groups as $id => $value) {
      $group = $this->sparqlStorage->load($id);
      $this->shareOnGroup($group);
      $group_labels[] = $group->label();
    }

    // Show a message if the content was shared on at least one group.
    if (!empty($groups)) {
      $this->messenger->addStatus('Item was shared on the following groups: ' . implode(', ', $group_labels) . '.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());
  }

  /**
   * Ajax callback to close the modal.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response that will close the modal.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the canonical URL cannot be generated for the shared entity.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand((string) $this->entity->toUrl()->toString()));

    return $response;
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function buildTitle(EntityInterface $entity): TranslatableMarkup {
    if ($this->isModal() || $this->isAjaxForm()) {
      return $this->t('Share on');
    }
    else {
      return $this->t('Share %title on', ['%title' => $entity->label()]);
    }
  }

  /**
   * Retrieves a list of collections where the entity can be shared on.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the current entity can be shared on.
   */
  protected function getShareableGroups(): array {
    // Being part also for the access check, do not allow the user to access
    // this page for entities without a field to store collections it is shared
    // in.
    if (!$this->entity->hasField($this->getSharedOnFieldName())) {
      return [];
    }

    $user_groups = $this->getUserGroupsByPermission($this->getPermissionForAction('share'));
    if ($parent = $this->getExcludedParent()) {
      unset($user_groups[$parent->id()]);
    }

    return array_diff_key($user_groups, array_flip($this->getAlreadySharedGroupIds()));
  }

  /**
   * Shares the current entity inside a group.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group where to share the entity on.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the current entity cannot be retrieved from the database.
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   *   Thrown when the entity storage is read only.
   */
  protected function shareOnGroup(RdfInterface $group): void {
    $current_ids = $this->getAlreadySharedGroupIds();
    $current_ids[] = $group->id();

    // Entity references do not ensure uniqueness.
    $current_ids = array_unique($current_ids);

    $this->entity->get($this->getSharedOnFieldName())->setValue($current_ids);
    $this->entity->save();
  }

  /**
   * Returns whether the form is displayed in a modal.
   *
   * @return bool
   *   TRUE if the form is displayed in a modal.
   *
   * @todo Remove when issue #2661046 is in.
   *
   * @see https://www.drupal.org/node/2661046
   */
  protected function isModal(): bool {
    return $this->getRequest()->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal';
  }

  /**
   * Returns whether the form is being handled with an ajax request.
   *
   * @return bool
   *   TRUE if the form is being handled through an AJAX request.
   */
  protected function isAjaxForm(): bool {
    return $this->getRequest()->query->has(FormBuilderInterface::AJAX_FORM_REQUEST);
  }

}
