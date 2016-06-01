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
   * Constant representing the pre-moderating parent.
   */
  const MODERATED = 1;

  /**
   * Constant representing the post-moderating parent.
   */
  const NON_MODERATED = 0;

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
    $from_state = $entity->field_news_state->first()->value;
    $parent = joinup_news_get_parent($entity);

    $is_moderated = self::MODERATED;
    if ($parent) {
      $is_moderated = ($parent->bundle() == 'collection') ?
        $parent->field_ar_moderation->first()->value :
        $parent->field_is_moderation->first()->value;
    }
    $to_state = $transition->getToState()->getId();
    $allowed_conditions = $this->getAllowedStatesMatrix();

    // Some transitions are not allowed per parent's moderation.
    // Check for the transitions allowed.
    if (!isset($allowed_conditions[$is_moderated][$to_state][$from_state])) {
      return FALSE;
    }

    // This method called whenever the transitions are checked even outside the
    // entity CRUD forms. Cases like this is e.g. when trying to edit the
    // settings of the field. For this reason, variables regarding the parent
    // entity are checked.
    if ($parent) {
      // Check if the user has one of the allowed system roles.
      $authorized_roles = $allowed_conditions[$is_moderated][$to_state][$from_state];
      $user = \Drupal::currentUser();
      if (array_intersect($authorized_roles, $user->getRoles())) {
        return TRUE;
      }

      // Check if the user has one of the allowed group roles.
      $membership = Og::getUserMembership($user->getAccount(), $parent);
      return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
    }

    return FALSE;
  }

  /**
   * Returns an array of allowed conditions for transitions.
   *
   * Format of array:
   * @code
   * [
   *   <parent moderation> => [
   *     <target state> => [
   *       <source state> => [
   *         <role1>
   *         <role2>
   *         .
   *         .
   *       ]
   *     ]
   *   ]
   * ]
   * @endcode
   *
   * The array is a four dimensions array. The first level contains the
   * value of the moderation of the parent entity. The second level is the
   * target state which is tha state that the entity transits to. Each target
   * state is an array of allowed source states which in every check, is the
   * current state of the entity. Finally, the source states are arrays
   * of roles that are allowed to perform this action.
   *
   * The reverse indexing here (source states
   * indexed by the target state) is to follow the state_machines module logic
   * of indexing states.
   *
   * @see joinup_news.workflows.yml
   *
   * @todo: This matrix could be stored in a configuration file instead.
   *
   * @return array
   *    An array of allowed conditions for the transitions.
   */
  protected function getAllowedStatesMatrix() {
    return [
      self::NON_MODERATED => [
        'draft' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'proposed' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'proposed' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'validated' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'in_assessment' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'validated' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'proposed' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'validated' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'in_assessment' => [
          'validated' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'deletion_request' => [],
      ],
      self::MODERATED => [
        'draft' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'proposed' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'validated' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'in_assessment' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
          'proposed' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'validated' => [
          'draft' => [
            'moderator',
            'rdf_entity-collection-facilitator',
          ],
          'validated' => [
            'moderator',
            'rdf_entity-collection-facilitator',
          ],
          'proposed' => [
            'moderator',
            'rdf_entity-collection-facilitator',
          ],
          'deletion_request' => [
            'moderator',
          ],
        ],
        'in_assessment' => [
          'validated' => [
            'moderator',
            'rdf_entity-collection-member',
          ],
        ],
        'deletion_request' => [
          'validated' => [
            'moderator',
            'rdf_entity-collection-administrator',
          ],
        ],
      ],
    ];
  }

}
