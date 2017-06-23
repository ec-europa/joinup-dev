<?php

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form class for share/unshare content forms.
 */
abstract class ShareContentFormBase extends FormBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The node being shared.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The RDF view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $rdfBuilder;

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
   */
  protected $rdfStorage;

  /**
   * Constructs a new ShareContentFormBase object.
   *
   * @param \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $rdf_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $rdf_builder
   *   The RDF view builder.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(RdfEntitySparqlStorage $rdf_storage, EntityViewBuilderInterface $rdf_builder, MembershipManagerInterface $membership_manager, AccountInterface $current_user) {
    $this->rdfStorage = $rdf_storage;
    $this->rdfBuilder = $rdf_builder;
    $this->membershipManager = $membership_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getViewBuilder('rdf_entity'),
      $container->get('og.membership_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The node being shared.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;

    return $form;
  }

  /**
   * Gets a list of collection ids where the current node is already shared.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collection ids where the current node is already shared in.
   */
  protected function getAlreadySharedCollectionIds() {
    return array_column($this->node->get('field_shared_in')->getValue(), 'target_id');
  }

  /**
   * Retrieves the collections a user is member of.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections the current user is member of, keyed by id.
   */
  protected function getUserCollections() {
    $groups = $this->membershipManager->getUserGroups($this->currentUser);

    if (empty($groups['rdf_entity'])) {
      return [];
    }

    $collections = array_filter($groups['rdf_entity'], function ($entity) {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      return $entity->bundle() === 'collection';
    });

    return $collections;
  }

  /**
   * Retrieves the collections a user is member of.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections the current user is member of, keyed by id.
   */
  protected function getUserCollectionsWhereFacilitator() {
    $memberships = $this->membershipManager->getMemberships($this->currentUser);

    if (empty($memberships)) {
      return [];
    }

    $role_id = 'rdf_entity-collection-facilitator';
    $collections = [];
    foreach ($memberships as $membership) {
      if ($membership->hasRole($role_id) && $membership->getGroup()->bundle() === 'collection') {
        $collection = $membership->getGroup();
        $collections[$collection->id()] = $collection;
      }
    }

    return $collections;
  }

}
