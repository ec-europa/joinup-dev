<?php

namespace Drupal\joinup_news\Guard;

use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class FulfilmentGuard.
 *
 * @todo: Fix the description.
 *
 * @package Drupal\joinup_news\Guard
 */
class FulfillmentGuard implements GuardInterface {

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    // New news can only go to draft or validated state.
    if ($entity->isNew() && !in_array($transition->getToState()->getId(), ['draft', 'validated'])) {
      return FALSE;
    }
    return TRUE;
  }

}
