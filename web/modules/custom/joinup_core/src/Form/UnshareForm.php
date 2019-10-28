<?php

namespace Drupal\joinup_core\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to unshare a community content from within collections.
 */
abstract class UnshareForm extends ShareFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unshare_content_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being unshared.
   *
   * @return array
   *   The form structure.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    $this->entity = $entity;

    $options = array_map(function ($collection) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      return $collection->label();
    }, $this->getCollections());

    $form['collections'] = [
      '#type' => 'checkboxes',
      '#title' => 'Collections',
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collections = [];
    // We can safely loop through these ids, as invalid options are handled
    // already by Drupal.
    foreach ($form_state->getValue('collections') as $id => $checked) {
      if ($checked) {
        $collection = $this->sparqlStorage->load($id);
        $this->removeFromCollection($collection);
        $collections[] = $collection->label();
      }
    }

    if (!empty($collections)) {
      $this->messenger->addStatus('Item was unshared from the following collections: ' . implode(', ', $collections) . '.');
    }
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

  /**
   * Gets a list of collections where the content can be unshared from the user.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the user is a facilitator and the content is
   *   shared.
   */
  protected function getCollections() {
    $collections = $this->getAlreadySharedCollectionIds();
    if (empty($collections)) {
      return $collections;
    }

    if ($this->currentUser->hasPermission('administer shared entities')) {
      return $this->sparqlStorage->loadMultiple($collections);
    }
    return array_intersect_key($this->getUserGroupsByPermission($this->getPermissionForAction('unshare')), array_flip($collections));
  }

  /**
   * Removes the current node from being shared inside a collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to remove the node.
   */
  protected function removeFromCollection(RdfInterface $collection) {
    // Flipping is needed to easily unset the value.
    $current_ids = array_flip($this->getAlreadySharedCollectionIds());
    unset($current_ids[$collection->id()]);
    $this->entity->get($this->getSharedInFieldName())->setValue(array_flip($current_ids));
    $this->entity->save();
  }

}
