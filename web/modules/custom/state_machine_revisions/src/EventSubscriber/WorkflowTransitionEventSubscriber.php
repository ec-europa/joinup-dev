<?php

declare(strict_types = 1);

namespace Drupal\state_machine_revisions\EventSubscriber;

use Drupal\Component\Plugin\PluginInspectionInterface;
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
   * Constructs a new WorkflowTransitionEventSubscriber object.
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
   * This event fires only on a transition i.e. state change. Same from-to
   * states will not fire the event so do not include changes that might take
   * place during a simple update.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   */
  public function handleRevision(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();

    // Verify if the new state is marked as published state.
    $is_published_state = $this->isPublishedState($event->getTransition()->getToState(), $event->getWorkflow());
    if ($entity instanceof EntityPublishedInterface) {
      $entity->setPublished($is_published_state);
    }

    // Add support for the Publication Date module.
    if ($entity->hasField('published_at') && $entity->getFieldDefinition('published_at')->getProvider() === 'publication_date') {
      // The publication date field applies its value depending on the status
      // of the entity on the `::preSave()` method on the field item class.
      // However, the publication date field is a base property and we are
      // handling the publication status of the entity on this subscriber
      // launched by the state item field. The state item field is an extra
      // field and its definition comes always after all base field definitions
      // when the field item `::preSave()` methods are called.
      // Furthermore, the only hook that can be used here is the
      // `hook_entity_bundle_field_info_alter()` which does not include the base
      // fields so we cannot reorder it there.
      // And last, the field hooks are fired before the entity hooks, so we
      // also cannot use a `hook_entity_presave()` or a
      // `hook_ENTITY_TYPE_presave()` to properly set the publication status
      // before calculating the publication date. Thus, we call the
      // `::applyDefaultValue()` to clear the value of the field and then the
      // `::preSave()` to force the field to re-calculate the value.
      // @see \Drupal\Core\Entity\ContentEntityStorageBase::invokeHook()
      // @see \Drupal\Core\Entity\EntityFieldManager::getFieldDefinitions()
      // @see \Drupal\publication_date\Plugin\Field\FieldType\PublicationDateItem::preSave()
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5544
      if ($entity->isNew()) {
        // In case it is a new entity, apply the default value (clear out the
        // values) because it might have already been set with the wrong status.
        $entity->get('published_at')->applyDefaultValue();
      }
      $entity->get('published_at')->preSave();
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
    // We rely on being able to inspect the plugin definition. Throw an error if
    // this is not the case.
    if (!$workflow instanceof PluginInspectionInterface) {
      $label = $workflow->getLabel();
      throw new \InvalidArgumentException("The '$label' workflow is not plugin based.");
    }

    // Retrieve the raw plugin definition, as all additional plugin settings
    // are stored there.
    $raw_workflow_definition = $workflow->getPluginDefinition();
    return !empty($raw_workflow_definition['states'][$state->getId()]['published']);
  }

}
