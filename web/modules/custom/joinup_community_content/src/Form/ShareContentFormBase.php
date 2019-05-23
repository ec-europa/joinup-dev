<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgRoleManagerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
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
   * The OG role manager service.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $roleManager;

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
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorage
   */
  protected $sparqlStorage;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ShareContentFormBase object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorage $sparql_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $rdf_builder
   *   The RDF view builder.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\og\OgRoleManagerInterface $role_manager
   *   The OG role manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(SparqlEntityStorage $sparql_storage, EntityViewBuilderInterface $rdf_builder, MembershipManagerInterface $membership_manager, OgRoleManagerInterface $role_manager, AccountInterface $current_user, MessengerInterface $messenger) {
    $this->sparqlStorage = $sparql_storage;
    $this->rdfBuilder = $rdf_builder;
    $this->membershipManager = $membership_manager;
    $this->roleManager = $role_manager;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getViewBuilder('rdf_entity'),
      $container->get('og.membership_manager'),
      $container->get('og.role_manager'),
      $container->get('current_user'),
      $container->get('messenger')
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
  protected function getAlreadySharedCollectionIds(): array {
    if (!$this->node->hasField('field_shared_in')) {
      return [];
    }

    return array_column($this->node->get('field_shared_in')->getValue(), 'target_id');
  }

  /**
   * Retrieves a list of groups of a user filtered by specific a permission.
   *
   * @param string $permission
   *   A permission to filter the roles of the user by.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   An array of groups.
   */
  protected function getUserGroupsByPermission($permission) {
    $roles = $this->roleManager->getRolesByPermissions([$permission], 'rdf_entity', 'collection');
    if (empty($roles)) {
      return [];
    }

    $groups = $this->membershipManager->getUserGroupsByRoles($this->currentUser, $roles);
    return empty($groups) ? [] : $groups['rdf_entity'];
  }

}
