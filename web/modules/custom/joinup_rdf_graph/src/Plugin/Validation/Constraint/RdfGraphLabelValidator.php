<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_rdf_graph\Entity\RdfGraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the 'rdf_graph' RDF entity label.
 *
 * @see \Drupal\joinup_rdf_graph\Plugin\Validation\Constraint\RdfGraphLabel
 */
class RdfGraphLabelValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new validator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\joinup_rdf_graph\Entity\RdfGraphInterface $rdf_graph */
    $rdf_graph = $items->getEntity();
    if (!$rdf_graph instanceof RdfGraphInterface) {
      return;
    }

    if (strtolower($item->value) === 'add') {
      $this->context->addViolation($constraint->messageReservedLabel, [
        '%value' => $item->value,
      ]);
      return;
    }

    $query = $this->entityTypeManager->getStorage('rdf_entity')->getQuery()
      ->condition('rid', 'rdf_graph')
      ->condition('label', $item->value);

    if (!empty($rdf_graph->id())) {
      $query->condition('id', $rdf_graph->id(), '<>');
    }

    if ($query->execute()) {
      $this->context->addViolation($constraint->messageUniqueLabel, [
        '%value' => $item->value,
      ]);
    }
  }

}
