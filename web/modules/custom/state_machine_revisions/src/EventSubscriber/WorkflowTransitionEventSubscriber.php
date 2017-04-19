<?php

namespace Drupal\state_machine_revisions\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\joinup_core\WorkflowHelperInterface;
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
   * The workflow helper.
   *
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Constructs a new WorkflowTransitionEventSubscriber object.
   *
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revisionManager
   *   The revision manager.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflowHelper
   *   The workflow helper.
   */
  public function __construct(RevisionManagerInterface $revisionManager, WorkflowHelperInterface $workflowHelper) {
    $this->revisionManager = $revisionManager;
    $this->workflowHelper = $workflowHelper;
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

    // Verify if the new state is marked as published state.
    $is_published_state = $this->isPublishedState($event->getToState(), $event->getWorkflow());
    if ($entity instanceof EntityPublishedInterface) {
      $entity->setPublished($is_published_state);
    }

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

    // Set revision as default when:
    // - the entity is new;
    // - the new state is a published one;
    // - the current default revision is not published.
    // @todo Change this to $entity->setDefaultRevision() once issue 2706337 is
    //   in.
    // @see https://www.drupal.org/node/2706337
    $entity->isDefaultRevision($entity->isNew() || $is_published_state || !$this->hasPublishedDefaultRevision($entity));
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
    return $this->workflowHelper->isWorkflowStatePublished($state->getId(), $workflow);
  }

}
