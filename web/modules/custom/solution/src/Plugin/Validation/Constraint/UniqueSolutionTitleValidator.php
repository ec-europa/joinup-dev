<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\solution\SolutionTitleDuplicateHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 *
 * This is the validator for the UniqueSolutionTitle.
 *
 * The following checks make sure that a solution must have a unique title among
 * within their collections.
 *
 * @see \Drupal\solution\Plugin\Validation\Constraint\UniqueSolutionTitle
 */
class UniqueSolutionTitleValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The solution title duplicate helper service.
   *
   * @var \Drupal\solution\SolutionTitleDuplicateHelperInterface
   */
  protected $solutionTitleHelper;

  /**
   * Constructs a new validator instance.
   *
   * @param \Drupal\solution\SolutionTitleDuplicateHelperInterface $solution_title_helper
   *   The solution title duplicate helper service.
   */
  public function __construct(SolutionTitleDuplicateHelperInterface $solution_title_helper) {
    $this->solutionTitleHelper = $solution_title_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('solution.title_duplicate_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $items->getEntity();

    if ($solution->bundle() !== 'solution') {
      throw new \InvalidArgumentException("This validator is designated only for solutions.");
    }

    // Either the solution's title is not unique within affiliation, or the
    // solution title has duplicates with other solutions, all without
    // affiliation. In the first case ::titleIsUniqueWithinAffiliation() returns
    // FALSE, in the latter NULL.
    // @see \Drupal\solution\SolutionTitleDuplicateHelperInterface::titleIsUniqueWithinAffiliation()
    if (!$this->solutionTitleHelper->titleIsUniqueWithinAffiliation($solution)) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $solution->getEntityType()->getLowercaseLabel(),
        '@field_name' => $items->getFieldDefinition()->getLabel(),
      ]);
    }
  }

}
