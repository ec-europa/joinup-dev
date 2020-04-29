<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Validation\Constraint;

use Drupal\Core\Database\Database;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 *
 * The validation is case insensitive. Works only for RDF entities. This
 * constraint was created explicitly for the RDF entity "Short ID" field.
 */
class UniqueShortIdInsensitiveValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();
    if ($entity->getEntityTypeId() !== 'rdf_entity') {
      throw new \RuntimeException('UniqueShortidInsensitiveConstraint can only be applied to collections and solutions because the SQL like condition is not case insensitive.');
    }

    // Short ID has cardinality 1. Does not need to iterate over other deltas.
    if (!$item = $items->first()) {
      return;
    }

    $bundle = $entity->bundle();
    $data = [
      'collection' => [
        'from' => 'FROM <http://joinup.eu/collection/published> FROM <http://joinup.eu/collection/draft>',
        'type' => '<http://www.w3.org/ns/dcat#Catalog>',
      ],
      'solution' => [
        'from' => 'FROM <http://joinup.eu/solution/published> FROM <http://joinup.eu/solution/draft>',
        'type' => '<http://www.w3.org/ns/dcat#Dataset>',
      ],
    ];

    $item_value = Database::getConnection()->escapeLike($item->value);
    $entity_id_where = $entity->isNew() ? '' : "FILTER (?entity_id NOT IN (<{$entity->id()}>))";
    $query = <<<QUERY
SELECT DISTINCT(?entity_id)
{$data[$bundle]['from']}
WHERE {
  ?entity_id a {$data[$bundle]['type']} .
  ?entity_id <http://purl.org/dc/terms/alternative> ?value .
  FILTER (lcase(?value) = lcase("{$item_value}")) .
  $entity_id_where
}
QUERY;
    $count = \Drupal::getContainer()->get('sparql.endpoint')->query($query)->count();

    if ($count) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $entity->getEntityType()->getSingularLabel(),
        '@field_name' => mb_strtolower($items->getFieldDefinition()->getLabel()),
      ]);
    }
  }

}
