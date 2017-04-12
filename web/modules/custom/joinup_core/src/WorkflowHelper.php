<?php

namespace Drupal\joinup_core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Contains helper methods to retrieve workflow related data from entities.
 */
class WorkflowHelper implements WorkflowHelperInterface {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The service that provides users to workflow guard classes.
   *
   * @var \Drupal\joinup_core\WorkflowUserProvider
   */
  protected $userProvider;

  /**
   * Constructs a WorkflowHelper.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The service that contains the current user.
   * @param \Drupal\joinup_core\WorkflowUserProvider $userProvider
   *   The service that provides users to workflow guard classes.
   */
  public function __construct(AccountProxyInterface $currentUser, WorkflowUserProvider $userProvider) {
    $this->currentUser = $currentUser;
    $this->userProvider = $userProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableStates(FieldableEntityInterface $entity, AccountInterface $user = NULL) {
    if ($user == NULL) {
      $user = $this->currentUser;
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    $this->userProvider->setUser($user);

    $field = $this->getEntityStateField($entity);
    $allowed_transitions = $field->getTransitions();

    $allowed_states = array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getToState()->getLabel();
    }, $allowed_transitions);

    return $allowed_states;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $user) {
    if ($user == NULL) {
      $user = $this->currentUser;
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    $this->userProvider->setUser($user);

    $field = $this->getEntityStateField($entity);

    return array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getLabel();
    }, $field->getTransitions());
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityStateFieldDefinitions(FieldableEntityInterface $entity) {
    return array_filter($entity->getFieldDefinitions(), function (FieldDefinitionInterface $field_definition) {
      return $field_definition->getType() == 'state';
    });
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityStateFieldDefinition(FieldableEntityInterface $entity) {
    if ($field_definitions = static::getEntityStateFieldDefinitions($entity)) {
      return reset($field_definitions);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateField(FieldableEntityInterface $entity) {
    $field_definition = $this->getEntityStateFieldDefinition($entity);
    if ($field_definition == NULL) {
      throw new \Exception('No state fields were found in the entity.');
    }
    return $entity->{$field_definition->getName()}->first();
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityStateField(FieldableEntityInterface $entity) {
    return (bool) static::getEntityStateFieldDefinitions($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function isWorkflowStatePublished($state_id, WorkflowInterface $workflow) {
    // We rely on being able to inspect the plugin definition. Throw an error if
    // this is not the case.
    if (!$workflow instanceof PluginInspectionInterface) {
      $label = $workflow->getLabel();
      throw new \InvalidArgumentException("The '$label' workflow is not plugin based.");
    }

    // Retrieve the raw plugin definition, as all additional plugin settings
    // are stored there.
    $raw_workflow_definition = $workflow->getPluginDefinition();
    return !empty($raw_workflow_definition['states'][$state_id]['published']);
  }

}
