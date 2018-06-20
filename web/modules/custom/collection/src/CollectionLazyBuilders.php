<?php

namespace Drupal\collection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a service for #lazy_builder callbacks for the Collection module.
 */
class CollectionLazyBuilders {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CollectionLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * Lazy builder callback; builds the Join Collection form.
   *
   * @param string $collection_id
   *   The collection ID.
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderJoinCollectionForm($collection_id) {
    $collection = $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
    return $this->formBuilder->getForm('\Drupal\collection\Form\JoinCollectionForm', $this->currentUser, $collection);
  }

}
