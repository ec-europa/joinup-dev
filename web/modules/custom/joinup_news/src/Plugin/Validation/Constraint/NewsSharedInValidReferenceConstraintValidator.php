<?php

namespace Drupal\joinup_news\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that valid references are selected in the "Shared in" news field.
 */
class NewsSharedInValidReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The news relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiate the NewsSharedInValidReferenceConstraintValidator validator.
   *
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The news relation manager.
   */
  public function __construct(JoinupRelationManager $relationManager) {
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $items */
    /** @var NewsSharedInValidReferenceConstraint $constraint */
    if (!$items->count()) {
      return;
    }

    $parent = $this->relationManager->getParent($items->getEntity());
    if (empty($parent)) {
      return;
    }

    foreach ($items as $delta => $item) {
      if ($item->entity->bundle() !== $parent->bundle()) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%field_name', $items->getFieldDefinition()->getLabel())
          ->setParameter('%label', $item->entity->label())
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }

}
