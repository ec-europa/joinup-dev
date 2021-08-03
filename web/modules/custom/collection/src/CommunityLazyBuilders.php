<?php

declare(strict_types = 1);

namespace Drupal\collection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\collection\Form\JoinCommunityForm;

/**
 * Defines a service for #lazy_builder callbacks for the Community module.
 */
class CommunityLazyBuilders {

  /**
   * The entity type manager service.
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
   * Constructs a new CommunityLazyBuilders object.
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
   * Lazy builder callback; builds the Join Community form.
   *
   * @param string $community_id
   *   The collection ID.
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderJoinCommunityForm($community_id) {
    $community = $this->entityTypeManager->getStorage('rdf_entity')->load($community_id);
    return $this->formBuilder->getForm(JoinCommunityForm::class, $this->currentUser, $community);
  }

}
