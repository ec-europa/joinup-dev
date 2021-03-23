<?php

declare(strict_types = 1);

namespace Drupal\sparql_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a testing entity.
 *
 * @ContentEntityType(
 *   id = "sparql_test",
 *   label = @Translation("SPARQL test entity"),
 *   handlers = {
 *     "storage" = "\Drupal\sparql_entity_storage\SparqlEntityStorage",
 *   },
 *   base_table = null,
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "sparql_type_test",
 *   links = {
 *     "canonical" = "/sparql_test/{sparql_test}",
 *     "edit-form" = "/sparql_test/{sparql_test}/edit",
 *     "delete-form" = "/sparql_test/{sparql_test}/delete",
 *     "collection" = "/sparql_test/list"
 *   },
 * )
 */
class SparqlTest extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
