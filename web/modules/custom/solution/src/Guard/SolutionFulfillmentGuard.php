<?php

namespace Drupal\joinup_news\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Guard class for the transitions of the solution entity.
 *
 * @package Drupal\solution\Guard
 */
class SolutionFulfillmentGuard implements GuardInterface {
  /**
   * Virtual state.
   */
  const NON_STATE = '__new__';

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    return TRUE;
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *    The solution entity.
   *
   * @return string
   *    The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(RdfInterface $entity) {
    if ($entity->isNew()) {
      return $entity->field_is_state->first()->value;
    }
    else {
      $unchanged_entity = \Drupal::entityTypeManager()->getStorage('rdf_entity')->loadUnchanged($entity->id());
      return $unchanged_entity->field_is_state->first()->value;
    }
  }

}
