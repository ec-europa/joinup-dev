<?php

namespace Drupal\joinup_news\Guard;

use Drupal\og\Og;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\Core\Entity\EntityInterface;

/**
 * Guard class for the transitions of the news entity.
 *
 * @package Drupal\joinup_news\Guard
 */
class FulfillmentGuard implements GuardInterface {
  /**
   * Contains a value replacement for the 'new' value.
   *
   * This is used to define that the entity is being created and the state
   * field is empty.
   *
   * @var string
   */
  const NEW_STATE = '__NEW__';

  /**
   * {@inheritdoc}
   *
   * We need to override default transitions allowed because this is also
   * dependant on the parent's moderation, system roles and organic groups
   * user roles. In the following method, the allowed transitions per
   * moderation are checked and then if the transition is allowed, the user
   * roles by the system and the organic groups are checked.
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $from_state = $entity->get('field_news_state')
      ->getValue() ?: self::NEW_STATE;

    // Check if the transition is allowed according to moderation of parent.
    $parent = joinup_news_get_parent($entity);
    $is_moderated = FALSE;
    if ($parent) {
      $is_moderated = (bool) (($parent->bundle() == 'collection') ? $parent->get('field_ar_moderation')
        ->getValue() : $parent->get('field_is_moderation')->getValue());
    }
    $to_state = $transition->getToState();
    $allowed_states = $this->getAllowedTransitions($is_moderated);
    if (!array_search($from_state, $allowed_states[$to_state])) {
      return FALSE;
    }

    // Check if the user has role permission.
    $user = \Drupal::currentUser();
    $system_roles = $this->getAllowedRoles();
    $has_system_role = FALSE;
    if (array_intersect($system_roles[$to_state], $user->getRoles())) {
      $has_system_role = TRUE;
    }

    // Check if the user has a role as a member of the group.
    $membership = Og::getUserMembership($user->getAccount(), $parent);
    $membership_roles = $this->getAllowedOgRoles();
    $has_membership_role = FALSE;
    if (array_intersect($membership_roles[$to_state], $membership->getRolesIds())) {
      $has_membership_role = TRUE;
    }

    if (!$has_system_role && !$has_membership_role) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns an array of transitions allowed according to entity moderation.
   *
   * The indexing is following the state machines logic where transitions
   * are indexed by the target state and each of them is an array of source
   * states.
   *
   * @param bool $moderated
   *    Whether the parent entity is moderated or not.
   *
   * @return array
   *    An array of allowed transitions indexed by the transition target state.
   */
  protected function getAllowedTransitions($moderated) {
    if ($moderated) {
      return [
        'draft' => [self::NEW_STATE],
        'proposed' => ['draft', 'validated', 'in_assessment', 'proposed'],
        'validated' => ['draft', 'validated', 'proposed', 'deletion_request'],
        'in_assessment' => ['validated'],
        'deletion_request' => ['validated'],
      ];
    }
    else {
      return [
        'draft' => [self::NEW_STATE],
        'proposed' => [
          self::NEW_STATE,
          'proposed',
          'validated',
          'in_assessment',
        ],
        'validate' => [self::NEW_STATE, 'draft', 'proposed', 'validated'],
        'in_assessment' => ['validated'],
        'deletion_request' => [''],
      ];
    }
  }

  /**
   * Returns the array of roles with permission to perform the transition.
   *
   * @return array
   *    An array indexed by the 'To' step of the workflow. Each key is an array
   *    of role machine names.
   */
  protected function getAllowedRoles() {
    return [
      'draft' => ['moderator'],
      'proposed' => ['moderator'],
      'validate' => ['moderator'],
      'in_assessment' => ['moderator'],
      'deletion_request' => ['moderator'],
    ];
  }

  /**
   * Returns the array of og roles with permission to perform the transition.
   *
   * @param bool $moderated
   *    Whether the parent entity is moderated or not.
   *
   * @return array
   *    An array indexed by the 'To' step of the workflow. Each key is an array
   *    of role machine names.
   */
  protected function getAllowedOgRoles($moderated) {
    if ($moderated) {
      return [
        'draft' => ['rdf_entity-collection-member'],
        'proposed' => ['rdf_entity-collection-member'],
        'validate' => ['rdf_entity-collection-facilitator'],
        'in_assessment' => ['rdf_entity-collection-member'],
        'deletion_request' => ['rdf_entity-collection-administrator'],
      ];
    }
    else {
      return [
        'draft' => ['rdf_entity-collection-member'],
        'proposed' => ['rdf_entity-collection-member'],
        'validate' => ['rdf_entity-collection-member'],
        'in_assessment' => ['rdf_entity-collection-member'],
        'deletion_request' => ['rdf_entity-collection-administrator'],
      ];
    }
  }

}
