<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when an entity is updated without changing its workflow state.
 *
 * @see \Drupal\joinup_workflow\Plugin\Field\FieldWidget\StateMachineButtons
 */
class UnchangedWorkflowStateUpdateEvent extends Event {

  /**
   * The entity being updated.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The workflow state.
   *
   * @var string
   */
  protected $state;

  /**
   * The label for the submit button.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $label;

  /**
   * The weight for the submit button.
   *
   * @var int
   */
  protected $weight;

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface
   */
  protected $access;

  /**
   * Constructs a notification event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   * @param string $state
   *   The workflow state.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label for the entity update submit button.
   * @param int $weight
   *   The weight for the entity update submit button.
   */
  public function __construct(EntityInterface $entity, string $state, TranslatableMarkup $label, int $weight) {
    $this->entity = $entity;
    $this->state = $state;
    $this->label = $label;
    $this->weight = $weight;
    $this->access = AccessResult::neutral();
  }

  /**
   * Returns the workflow state.
   *
   * @return string
   *   The workflow state.
   */
  public function getState(): string {
    return $this->state;
  }

  /**
   * Returns the entity being updated.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Returns the label for the submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getLabel(): TranslatableMarkup {
    return $this->label;
  }

  /**
   * Sets the label for the submit button.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label text.
   *
   * @return $this
   */
  public function setLabel(TranslatableMarkup $label): self {
    $this->label = $label;
    return $this;
  }

  /**
   * Returns the weight for the submit button.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * Sets the weight for the submit button.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight(int $weight): self {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Returns the access result.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function getAccess(): AccessResultInterface {
    return $this->access;
  }

  /**
   * Sets the access result.
   *
   * Any listener that forbids access will make the same state update on the
   * entity unavailable for the user.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access result object.
   *
   * @return $this
   */
  public function setAccess(AccessResultInterface $access): self {
    $this->access = $this->access->orIf($access);
    return $this;
  }

}
