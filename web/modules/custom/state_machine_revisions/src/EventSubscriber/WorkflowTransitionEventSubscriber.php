<?php

namespace Drupal\state_machine_revisions\EventSubscriber;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle revisions on workflow-enabled entities.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'state_machine.pre_transition' => 'handleRevision',
    ];
  }

  /**
   * Sets an entity revision to be the default based on the workflow.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   */
  public function handleRevision(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();

    // Bail out if this entity doesn't implement revisions. We do not limit
    // strictly to content entities.
    if (!$entity instanceof RevisionableInterface) {
      return;
    }

    // Since all content entities implement the RevisionableInterface, we have
    // to check if the entity has the revision support and if it is set to
    // create a new revision.
    if (!$entity->getEntityType()->hasKey('revision') || !$entity->isNewRevision()) {
      return;
    }

    // Retrieve the raw plugin definition, as all additional plugin settings
    // are stored there.
    $raw_workflow_definition = $event->getWorkflow()->getPluginDefinition();
    $to_state_id = $event->getToState()->getId();
    // Verify if the new state is marked as published state.
    $is_published_state = !empty($raw_workflow_definition['states'][$to_state_id]['published']);
    // Set revision as default always for new entities.
    $entity->isDefaultRevision($entity->isNew() || $is_published_state);

    if ($entity instanceof EntityPublishedInterface) {
      $entity->setPublished($is_published_state);
    }
  }

}
