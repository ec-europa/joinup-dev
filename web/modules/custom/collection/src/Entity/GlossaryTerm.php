<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\node\Entity\Node;

/**
 * Entity subclass for the 'collection' bundle.
 */
class GlossaryTerm extends Node implements GlossaryTermInterface {

  use NodeCollectionContentTrait;

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  public static function create(array $values = []): GlossaryTermInterface {
    // Delegate to the parent method. This is only overridden to provide the
    // correct return type.
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getSynonyms(): array {
    return array_map(function (array $field_item): string {
      return $field_item['value'];
    }, $this->get('field_glossary_synonyms')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): string {
    /** @var \Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem $field_item */
    $field_item = $this->getFirstItem('field_glossary_definition');

    // The definition is a required field so it should normally be present on
    // the entity. If not, this is probably a new, unpopulated entity.
    if (empty($field_item)) {
      return '';
    }

    $summary = $field_item->summary ?? '';
    if (!empty($summary)) {
      return trim($summary);
    }

    // If no summary is set, fall back to a shortened version of the main
    // definition.
    $definition = $field_item->value ?? '';
    $format = $field_item->format ?? NULL;

    $summary = text_summary($definition, $format, 300);
    return trim(strip_tags($summary));
  }

}
