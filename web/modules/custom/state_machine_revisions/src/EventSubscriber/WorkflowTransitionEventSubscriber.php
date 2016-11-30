<?php

namespace Drupal\state_machine_revisions\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowState;
use Drupal\state_machine_revisions\RevisionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle revisions on workflow-enabled entities.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * The revision manager.
   *
   * @var \Drupal\state_machine_revisions\RevisionManagerInterface
   */
  protected $revisionManager;

  /**
   * Constructs a new EntityRevisionConverter object.
   *
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revisionManager
   *   The revision manager.
   */
  public function __construct(RevisionManagerInterface $revisionManager) {
    $this->revisionManager = $revisionManager;
  }

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
    if (!$entity->getEntityType()->isRevisionable() || !$entity->isNewRevision()) {
      return;
    }

    // Verify if the new state is marked as published state.
    $is_published_state = $this->isPublishedState($event->getToState(), $event->getWorkflow());
    // Set revision as default when:
    // - the entity is new;
    // - the new state is a published one;
    // - the current default revision is not published.
    $entity->isDefaultRevision($entity->isNew() || $is_published_state || !$this->hasPublishedDefaultRevision($entity));

    if ($entity instanceof EntityPublishedInterface) {
      $entity->setPublished($is_published_state);
    }
  }

  /**
   * Checks if an entity has a published default revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity has a published default revision, FALSE otherwise.
   */
  protected function hasPublishedDefaultRevision(ContentEntityInterface $entity) {
    // New entities don't have revisions, obviously.
    if ($entity->isNew()) {
      return FALSE;
    }

    $default_revision = $this->revisionManager->loadDefaultRevision($entity);
    // The entity needs to implement the published interface, or of course it's
    // not published.
    if (!$default_revision instanceof EntityPublishedInterface) {
      return FALSE;
    }

    return $default_revision->isPublished();
  }

  /**
   * Checks if a state is set as published in a certain workflow.
   *
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowState $state
   *   The state to check.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow the state belongs to.
   *
   * @return bool
   *   TRUE if the state is set as published in the workflow, FALSE otherwise.
   */
  protected function isPublishedState(WorkflowState $state, WorkflowInterface $workflow) {
    // Retrieve the raw plugin definition, as all additional plugin settings
    // are stored there.
    $raw_workflow_definition = $workflow->getPluginDefinition();
    $state_id = $state->getId();

    return !empty($raw_workflow_definition['states'][$state_id]['published']);
  }

}
