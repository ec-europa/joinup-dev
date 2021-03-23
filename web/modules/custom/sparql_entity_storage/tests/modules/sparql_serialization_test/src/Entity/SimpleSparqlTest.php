<?php

declare(strict_types = 1);

namespace Drupal\sparql_serialization_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a testing entity.
 *
 * @ContentEntityType(
 *   id = "simple_sparql_test",
 *   label = @Translation("Simple SPARQL test entity"),
 *   handlers = {
 *     "storage" = "\Drupal\sparql_entity_storage\SparqlEntityStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *   },
 *   bundle_entity_type = "simple_sparql_type_test",
 * )
 */
class SimpleSparqlTest extends ContentEntityBase {

  /**
   * The bundle.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('uri')->setLabel(t('ID'));
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE);
    return $fields;
  }

}
