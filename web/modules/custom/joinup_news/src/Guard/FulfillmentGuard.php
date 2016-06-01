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
   * {@inheritdoc}
   *
   * We need to override default transitions allowed because this is also
   * dependant on the parent's moderation, system roles and organic groups
   * user roles. In the following method, the allowed transitions per
   * moderation are checked and then if the transition is allowed, the user
   * roles by the system and the organic groups are checked.
   *
   * This method called whenever the transitions are checked even outside the
   * entity CRUD forms. Cases like this is e.g. when trying to edit the settings
   * of the field.
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $from_state = $entity->field_news_state->first()->value;

    // Check if the transition is allowed according to moderation of parent.
    // @todo: This can be empty if the states are accessed outside the scope of the create.
    $parent = joinup_news_get_parent($entity);

    $is_moderated = TRUE;
    if ($parent) {
      $is_moderated = (bool) ($parent->bundle() == 'collection') ?
        $parent->field_ar_moderation->first()->value :
        $parent->field_is_moderation->first()->value;
    }
    $to_state = $transition->getToState()->getId();
    $allowed_states = $this->getAllowedTransitions($is_moderated);
    if (!in_array($from_state, $allowed_states[$to_state])) {
      return FALSE;
    }

    // Check if the user has role permission.
    $user = \Drupal::currentUser();
    $system_roles = $this->getAllowedRoles();
    if (array_intersect($system_roles[$to_state], $user->getRoles())) {
      return TRUE;
    }

    // Check if the user has a role as a member of the group.
    $membership = Og::getUserMembership($user->getAccount(), $parent);
    if (empty($membership)) {
      return FALSE;
    }
    $membership_roles = $this->getAllowedOgRoles($is_moderated);
    if (array_intersect($membership_roles[$to_state], $membership->getRolesIds())) {
      return TRUE;
    }

    return FALSE;
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
        'draft' => ['draft'],
        'proposed' => ['draft', 'validated', 'in_assessment', 'proposed'],
        'validated' => ['draft', 'validated', 'proposed', 'deletion_request'],
        'in_assessment' => ['validated'],
        'deletion_request' => ['validated'],
      ];
    }
    else {
      return [
        'draft' => ['draft'],
        'proposed' => ['draft', 'proposed', 'validated', 'in_assessment'],
        'validated' => ['new', 'draft', 'proposed', 'validated'],
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
    // The moderator can perform all transitions when available.
    return [
      'draft' => ['moderator'],
      'proposed' => ['moderator'],
      'validated' => ['moderator'],
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
    // Roles are supplementary. This means that if one role has permission
    // for a certain action, this is his permission. If a user needs to perform
    // this action, he should have this role assigned and not assign permissions
    // to multiple roles for no reason but convenience.
    if ($moderated) {
      return [
        'draft' => ['rdf_entity-collection-member'],
        'proposed' => ['rdf_entity-collection-member'],
        'validated' => ['rdf_entity-collection-facilitator'],
        'in_assessment' => ['rdf_entity-collection-member'],
        'deletion_request' => ['rdf_entity-collection-administrator'],
      ];
    }
    else {
      return [
        'draft' => ['rdf_entity-collection-member'],
        'proposed' => ['rdf_entity-collection-member'],
        'validated' => ['rdf_entity-collection-member'],
        'in_assessment' => ['rdf_entity-collection-member'],
        'deletion_request' => ['rdf_entity-collection-administrator'],
      ];
    }
  }

}
