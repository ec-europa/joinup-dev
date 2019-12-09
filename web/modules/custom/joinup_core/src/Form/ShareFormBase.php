<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Form;

use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgRoleManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form class for share/unshare entity forms.
 */
abstract class ShareFormBase extends FormBase {

  const SHARED_IN_FIELD_NAMES = [
    'rdf_entity' => [
      'solution' => 'field_is_shared_in',
    ],
    'node' => [
      'discussion' => 'field_shared_in',
      'document' => 'field_shared_in',
      'event' => 'field_shared_in',
      'news' => 'field_shared_in',
    ],
  ];

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
   * The entity being shared.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

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
   * Returns the entity to be shared.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity to be shared.
   */
  public function getEntity(): FieldableEntityInterface {
    return $this->entity;
  }

  /**
   * Gets a list of collection ids where the current entity is already shared.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collection ids where the current entity is already shared in.
   */
  protected function getAlreadySharedCollectionIds(): array {
    if (!$this->getSharedInFieldName() || !$this->entity->hasField($this->getSharedInFieldName())) {
      return [];
    }

    return array_column($this->entity->get($this->getSharedInFieldName())->getValue(), 'target_id');
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

    $groups = $this->membershipManager->getUserGroupsByRoleIds($this->currentUser->id(), array_keys($roles));
    return empty($groups) ? [] : $groups['rdf_entity'];
  }

  /**
   * Returns the name of the field that the entity uses for being shared.
   *
   * @return string|null
   *   The field name or null if not configured.
   */
  protected function getSharedInFieldName(): ?string {
    return self::SHARED_IN_FIELD_NAMES[$this->entity->getEntityTypeId()][$this->entity->bundle()];
  }

  /**
   * Returns the name of the permission needed for the given action.
   *
   * @param string $action
   *   The action name. Can be either 'share' or 'unshare'.
   *
   * @return string
   *   The permission name.
   */
  protected function getPermissionForAction(string $action): string {
    if (!in_array($action, ['share', 'unshare'])) {
      throw new \InvalidArgumentException('Only "share" and "unshare" are allowed as an action name.');
    }
    $type = $this->entity->getEntityTypeId() === 'node' ? 'content' : $this->entity->getEntityTypeId();
    return "{$action} {$this->entity->bundle()} {$type}";
  }

  /**
   * Returns a list of groups that the entity cannot be shared in.
   *
   * For nodes, this is the parent group. For rdf entities, it is the affiliated
   * collection of the solution.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The affiliated or parent collection, if one exists.
   */
  protected function getExcludedParent(): ?RdfInterface {
    if ($this->entity->getEntityTypeId() === 'node') {
      return $this->relationManager->getParent($this->entity);
    }
    else {
      return $this->entity->get('collection')->isEmpty() ? NULL : $this->entity->get('collection')->first()->entity;
    }
  }

}
