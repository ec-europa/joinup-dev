<?php

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;

/**
 * Access handler for entities with a workflow.
 *
 * @todo: More information should be inserted here.
 * @todo: If we are going with a unified way, a readme should include the
 *  workflow creation process.
 * @todo: Add cacheability to all access.
 *
 * @package Drupal\joinup_core
 */
class JoinupWorkflowAccessControlHandler {

  /**
   * The state field machine name.
   */
  const STATE_FIELD = 'field_state';

  /**
   * Flag for pre-moderated groups.
   */
  const PRE_MODERATION = 1;

  /**
   * Flag for post-moderated groups.
   */
  const POST_MODERATION = 0;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * Only facilitators are allowed to create content.
   */
  const ELIBRARY_ONLY_FACILITATORS = 0;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * Only users that are subscribed to the group are allowed to create content.
   */
  const ELIBRARY_MEMBERS_FACILITATORS = 1;

  /**
   * Flag for option for the eLibrary creation field.
   *
   * All registered users can create content.
   */
  const ELIBRARY_REGISTERED_USERS = 2;

  /**
   * The machine name of the default workflow for groups.
   *
   * @todo: Change the group workflows to 'default'.
   */
  const WORKFLOW_DEFAULT = 'default';

  /**
   * The machine name of the pre moderated workflow for group content.
   *
   * @todo: Backport this to entity types other than document.
   */
  const WORKFLOW_PRE_MODERATED = 'pre_moderated';

  /**
   * The machine name of the post moderated workflow for group content.
   *
   * @todo: Backport this to entity types other than document.
   */
  const WORKFLOW_POST_MODERATED = 'post_moderated';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupDocumentRelationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $og_membership_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $og_membership_manager;
  }

  /**
   * Main handler for access checks for group content in joinup.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group content entity object.
   * @param string $operation
   *    The CRUD operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    The result of the access check.
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ((!in_array($entity->getEntityTypeId(), ['rdf_entity', 'node']))) {
      return AccessResult::neutral();
    }

    switch ($operation) {
      case 'view':
        return $this->entityViewAccess($entity, $account);

      case 'create':
      case 'update':
      case 'edit':
        $allowed_transitions = $entity->get('field_state')->first()->getTransitions();
        return AccessResult::allowedIf(!empty($allowed_transitions));

      case 'delete':
        return $this->entityDeleteAccess($entity, $account);

    }

    return AccessResult::neutral();
  }

  /**
   * Access check for the view operation.
   *
   * The following checks take place with the same priority:
   * - If the parent entity is not viewable, only moderators, facilitators and
   * the node owner can see the entity regardless of the state.
   * - If the user has the permission in the membership to view all unpublished
   * content, he is granted access.
   * - Otherwise we return neutral.
   * Note that admin permissions are already checked in the entity access
   * handler class.
   * If we return neutral, the entity access handler class, will automatically
   * take care of the user being able to view his own unpublished content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    The access result check.
   */
  protected function entityViewAccess(EntityInterface $entity, AccountInterface $account) {
    $parent = $this->getEntityParent($entity);
    $access_handler = $this->entityTypeManager->getAccessControlHandler('rdf_entity');
    if (!$access_handler->access($parent, 'view', $account)) {
      return AccessResult::forbiddenIf($entity->getOwnerId() !== $account->id());
    }

    $membership = Og::getMembership($parent, $account);
    if (empty($membership)) {
      return AccessResult::neutral();
    }

    $entity_type = ($entity->getEntityTypeId() === 'node') ? 'content' : 'rdf_entity';
    if (!$entity->isPublished() && $membership->hasPermission("view any unpublished {$entity_type}")) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * Access check for delete operation.
   *
   * Delete operation should not be interrupted by states. The user either has
   * or has not the permission.
   * The following checks take place with the given priority:
   * - If the user has global permission to delete any entity of the given
   * bundle, he is being granted access.
   * - If the user has group permission to delete any entity of the given
   * bundle, he is being granted access.
   * - If the user is the owner of the entity, he is allowed only if the parent
   * is in a post moderated state.
   * In all other cases, we allow entity access handler to decide.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    The access result.
   */
  protected function entityDeleteAccess(EntityInterface $entity, AccountInterface $account) {
    $entity_type = ($entity->getEntityTypeId() === 'node') ? 'content' : 'rdf_entity';
    if ($account->hasPermission("delete any {$entity->bundle()} {$entity_type}")) {
      return AccessResult::allowed();
    }

    $parent = $this->getEntityParent($entity);
    $membership = Og::getMembership($parent, $account);
    if (!empty($membership) && $membership->hasPermission("delete any {$entity->bundle()} {$entity_type}")) {
      return AccessResult::allowed();
    }

    $workflow = $this->getEntityWorkflow($entity);
    if ($workflow === self::WORKFLOW_PRE_MODERATED) {
      return AccessResult::forbiddenIf(!$account->hasPermission("delete any {$entity->bundle()} {$entity_type}"));
    }

    return AccessResult::neutral();
  }

  /**
   * Helper method to retrieve the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the entity belongs to, or NULL when no group is found.
   */
  protected function getEntityParent(EntityInterface $entity) {
    $groups = $this->membershipManager->getGroups($entity);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * Returns the appropriate workflow to use for the passed entity.
   *
   * If the entity is a group, then the workflow is the default workflow.
   * If the entity is a group content, then the workflow is dependant to the
   * moderation settings of the parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group content entity.
   *
   * @return string
   *    The id of the workflow to use.
   */
  protected function getEntityWorkflow(EntityInterface $entity) {
    if (Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      return self::WORKFLOW_DEFAULT;
    }

    $parent = $this->getEntityParent($entity);
    if (empty($parent) || in_array($parent->bundle(), ['collection', 'solution'])) {
      return self::WORKFLOW_PRE_MODERATED;
    }
    $fields = [
      'collection' => 'field_ar_moderation',
      'solution' => 'field_is_moderation',
    ];

    $moderation = $parent->{$fields[$parent->bundle()]}->value;
    $workflow_id = $moderation === TRUE ? self::WORKFLOW_PRE_MODERATED : self::WORKFLOW_POST_MODERATED;
    return $workflow_id;
  }

}
