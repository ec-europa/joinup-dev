<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\collection\Entity\GlossaryTermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for the GlossaryTermName constraint.
 */
class GlossaryTermNameValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new constraint validator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
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
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    if ($items->isEmpty()) {
      return;
    }

    $glossary_term = $items->getEntity();
    $field_definition = $items->getFieldDefinition();
    if (!$glossary_term instanceof GlossaryTermInterface || $field_definition->getName() !== 'title') {
      throw new \InvalidArgumentException("The 'GlossaryTermName' can be used only on field 'title' of glossary terms.");
    }

    $storage = $this->entityTypeManager->getStorage('node');

    $query = $storage->getQuery()
      ->condition('type', 'glossary')
      ->condition('og_audience', $glossary_term->get('og_audience')->target_id);
    // ->condition('field_glossary_synonyms', $glossary_term->label());
    if ($glossary_term_id = $glossary_term->id()) {
      // On update, filter out the current entity.
      $query->condition('nid', $glossary_term_id, '<>');
    }
    $nids = array_values($query->execute());

    $synonyms = [];
    foreach ($storage->loadMultiple($nids) as $other_glossary_term) {
      foreach ($other_glossary_term->get('field_glossary_synonyms') as $item) {
        // We do a case insensitive match.
        $synonyms[\mb_strtolower($item->value)] = $other_glossary_term;
      }
    }

    $lowercased_label = \mb_strtolower($glossary_term->label());
    if (isset($synonyms[$lowercased_label])) {
      $this->context->addViolation($constraint->message, [
        '%name' => $glossary_term->label(),
        ':url' => $synonyms[$lowercased_label]->toUrl()->toString(),
        '@glossary' => $synonyms[$lowercased_label]->label(),
      ]);
    }
  }

}
