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
 * Provides a validator for the GlossaryTermSynonyms constraint.
 */
class GlossaryTermSynonymsValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
    if (!$glossary_term instanceof GlossaryTermInterface || $field_definition->getName() !== 'field_glossary_synonyms') {
      throw new \InvalidArgumentException("The 'GlossaryTermSynonyms' can be used only on field 'field_glossary_synonyms' of glossary terms.");
    }

    $glossary_term_label_lowercased = \mb_strtolower($glossary_term->label());
    $synonyms = [];
    foreach ($items as $item) {
      $synonym_lowercased = \mb_strtolower($item->value);
      if ($synonym_lowercased === $glossary_term_label_lowercased) {
        // Don' allow synonyms with the same name as the glossary term name.
        $this->context->addViolation($constraint->messageSameAsTermName, [
          '%name' => $glossary_term->label(),
        ]);
      }
      if (in_array($synonym_lowercased, $synonyms, TRUE)) {
        // Don't allow synonym duplication.
        $this->context->addViolation($constraint->messageDuplicate, [
          '%synonym' => $item->value,
        ]);
      }
      $synonyms[$synonym_lowercased] = $item->value;
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'glossary')
      ->condition('og_audience', $glossary_term->get('og_audience')->target_id);
    if ($glossary_term_id = $glossary_term->id()) {
      // On update, filter out the current entity.
      $query->condition('nid', $glossary_term_id, '<>');
    }
    $nids = array_values($query->execute());

    $other_glossary_term_synonyms = [];
    foreach ($storage->loadMultiple($nids) as $other_glossary_term) {
      foreach ($other_glossary_term->get('field_glossary_synonyms') as $item) {
        // We do a case insensitive match.
        $other_glossary_term_synonyms[\mb_strtolower($item->value)] = $other_glossary_term;
      }
    }

    $used_synonyms = [];
    foreach ($other_glossary_term_synonyms as $other_glossary_term) {
      $other_glossary_term_label_lowercased = \mb_strtolower($other_glossary_term->label());
      if (isset($synonyms[$other_glossary_term_label_lowercased])) {
        $used_synonyms[$other_glossary_term_label_lowercased] = [
          'synonym' => $synonyms[$other_glossary_term_label_lowercased],
          'entity' => $other_glossary_term,
        ];
      }
      foreach ($other_glossary_term->get('field_glossary_synonyms') as $item) {
        $other_glossary_term_synonym_lowercased = \mb_strtolower($item->value);
        if (in_array($other_glossary_term_synonym_lowercased, $synonyms)) {
          $used_synonyms[$other_glossary_term_synonym_lowercased] = [
            'synonym' => $synonyms[$item->value],
            'entity' => $other_glossary_term,
          ];
        }
      }
    }

    if ($used_synonyms) {
      $args = [];
      $delta = 0;
      $occurrences = [];
      // Build a readable list of already used synonyms.
      foreach ($used_synonyms as $data) {
        $synonym_placeholder = "%synonym_{$delta}";
        $url_placeholder = ":url_{$delta}";
        $label_placeholder = "@label_{$delta}";
        $occurrences[] = "{$synonym_placeholder} in <a href=\"{$url_placeholder}\">{$label_placeholder}</a>";
        $args += [
          $synonym_placeholder => $data['synonym'],
          $url_placeholder => $data['entity']->toUrl()->toString(),
          $label_placeholder => $data['entity']->label(),
        ];
        $delta++;
      }
      $this->context->addViolation($constraint->messageInOtherTerms . implode(', ', $occurrences) . '.', $args);
    }
  }

}
