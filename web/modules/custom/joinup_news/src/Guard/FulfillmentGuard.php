<?php

namespace Drupal\joinup_news\Guard;

use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\rdf_entity\Entity\Rdf;
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
   * Virtual state.
   */
  const NON_STATE = '__new__';

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
    $to_state = $transition->getToState()->getId();
    // Disable virtual state.
    if ($to_state == self::NON_STATE) {
      return FALSE;
    }

    $from_state = $this->getState($entity);
    $parent = $this->getParent($entity);

    $is_moderated = self::MODERATED;
    if ($parent) {
      $is_moderated = ($parent->bundle() == 'collection') ?
        $parent->field_ar_moderation->first()->value :
        $parent->field_is_moderation->first()->value;
    }
    $allowed_conditions = \Drupal::config('joinup_news.settings')->get('transitions');

    // Some transitions are not allowed per parent's moderation.
    // Check for the transitions allowed.
    // @see: joinup_news.settings.yml
    if (!isset($allowed_conditions[$is_moderated][$to_state][$from_state])) {
      return FALSE;
    }

    // This Guard class's method called whenever the transitions are checked
    // even outside the entity CRUD forms. Cases like this is e.g. when trying
    // to edit the settings of the field.
    // In these cases, there is no parent entity so we need to check for it.
    if (empty($parent)) {
      return FALSE;
    }

    // Check if the user has one of the allowed system roles.
    $authorized_roles = $allowed_conditions[$is_moderated][$to_state][$from_state];
    $user = \Drupal::currentUser();
    if (array_intersect($authorized_roles, $user->getRoles())) {
      return TRUE;
    }

    // Check if the user has one of the allowed group roles.
    $membership = Og::getMembership($parent, $user->getAccount());
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * The state_machine module uses a protected property called initialValue to
   * get the initial state which is inaccessible. During an entity update, the
   * typedDataManager attempts to validate the field but the constraint again
   * calls for the Guard to check the allowed states.
   * The issue is that the entity object already carries the new value
   * at this point, so it attempts to check a to_state to to_state transition.
   * In order to check the initial value, we get the unchanged version of the
   * object from the database.
   *
   * @param \Drupal\node\NodeInterface $entity
   *    The node entity.
   *
   * @return string
   *    The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(NodeInterface $entity) {
    if ($entity->isNew()) {
      return $entity->field_news_state->first()->value;
    }
    else {
      $unchanged_entity = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($entity->id());
      return $unchanged_entity->field_news_state->first()->value;
    }
  }

  /**
   * Returns the owner entity of this node if it exists.
   *
   * The news entity can belong to a collection or a solution, depending on
   * how it was created. This function will return the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The news content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *    The parent of the entity. This can be a collection or a solution.
   *    If there is no parent found, return NULL.
   *
   * @todo This is currently called in joinup_news_node_access() as a workaround
   *   for the lack of access checking in OG. Turn this in a protected method as
   *   soon as this is fixed in OG.
   *
   * @see joinup_news_node_access()
   * @see https://github.com/amitaibu/og/pull/217
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2622
   */
  public static function getParent(EntityInterface $entity) {
    $parent = NULL;
    if (!empty($entity->og_audience->first()->target_id)) {
      /** @var \Drupal\rdf_entity\RdfInterface $parent */
      $parent = Rdf::load($entity->og_audience->first()->target_id);
    }
    return $parent;
  }

}
