<?php

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to unshare a community content from within collections.
 */
class UnshareContentForm extends ShareContentFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unshare_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form = parent::buildForm($form, $form_state, $node);

    $options = array_map(function ($collection) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      return $collection->label();
    }, $this->getCollections());

    $form['collections'] = [
      '#type' => 'checkboxes',
      '#title' => 'Collections',
      '#options' => $options,
      // All these collections are already shared, so all checkboxes are checked
      // by default.
      '#default_value' => array_keys($options),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($form_state->getValue('collections') as $id => $checked) {
      if (!$checked) {
        $collection = $this->rdfStorage->load($id);
        $this->removeFromCollection($collection);
      }
    }

    drupal_set_message('Sharing updated.');

    $form_state->setRedirectUrl($this->node->toUrl());
  }

  /**
   * Access check for the form route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity being shared.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed if there is at least one collection where the node can be shared.
   */
  public function access(NodeInterface $node) {
    $this->node = $node;

    return AccessResult::allowedIf(!empty($this->getCollections()));
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function getTitle(NodeInterface $node) {
    return $this->t('Unshare %title from', ['%title' => $node->label()]);
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
      return [];
    }

    return array_intersect_key($this->getUserCollectionsWhereFacilitator(), array_flip($collections));
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
    $this->node->get('field_shared_in')->setValue(array_flip($current_ids));
    $this->node->save();
  }

}
