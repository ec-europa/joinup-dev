<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\RdfEntityUuidFieldItemList;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\sparql_entity_storage\SparqlGraphInterface;
use Drupal\user\UserInterface;

/**
 * Defines the RDF entity.
 *
 * @ingroup rdf_entity
 *
 * @ContentEntityType(
 *   id = "rdf_entity",
 *   label = @Translation("Rdf entity"),
 *   handlers = {
 *     "storage" = "\Drupal\sparql_entity_storage\SparqlEntityStorage",
 *     "view_builder" = "Drupal\rdf_entity\RdfEntityViewBuilder",
 *     "list_builder" = "Drupal\rdf_entity\Entity\Controller\RdfListBuilder",
 *     "form" = {
 *       "default" = "Drupal\rdf_entity\Form\RdfForm",
 *       "add" = "Drupal\rdf_entity\Form\RdfForm",
 *       "edit" = "Drupal\rdf_entity\Form\RdfForm",
 *       "delete" = "\Drupal\rdf_entity\Form\RdfDeleteForm",
 *     },
 *     "access" = "Drupal\rdf_entity\RdfAccessControlHandler",
 *   },
 *   base_table = null,
 *   admin_permission = "administer rdf entity",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uid" = "uid",
 *     "bundle" = "rid",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "rdf_type",
 *   links = {
 *     "canonical" = "/rdf_entity/{rdf_entity}",
 *     "edit-form" = "/rdf_entity/{rdf_entity}/edit",
 *     "delete-form" = "/rdf_entity/{rdf_entity}/delete",
 *     "collection" = "/rdf_entity/list"
 *   },
 *   field_ui_base_route = "entity.rdf_type.edit_form",
 *   permission_granularity = "bundle",
 *   common_reference_target = TRUE,
 * )
 */
class Rdf extends ContentEntityBase implements RdfInterface {

  use EntityChangedTrait;

  /**
   * Entity bundle.
   *
   * @var string
   */
  protected $rid;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Get the bundle of the entity.
   */
  public function getRid() {
    return $this->get('rid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->hasFieldMapping('created') ? $this->get('created')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    if ($this->hasFieldMapping('created')) {
      $this->set('created', $timestamp);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->hasFieldMapping('changed') ? $this->get('changed')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    if ($this->hasFieldMapping('changed')) {
      $this->set('changed', $timestamp);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('ID'))
      ->setTranslatable(FALSE);

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rdf Type'))
      ->setDescription(t('The Rdf type of this entity.'))
      ->setSetting('target_type', 'rdf_type');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\rdf_entity\Entity\Rdf::getCurrentUserId')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the entity was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The UUID field is provided just to allow core or modules to retrieve RDF
    // entities by UUID. In fact UUID is computed with the same value as ID.
    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setClass(RdfEntityUuidFieldItemList::class);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Langcode'))
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    return $fields;
  }

  /**
   * Get the bundle from the entity.
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): RdfInterface {
    $this->set('label', $name);
    return $this;
  }

  /**
   * Get weight.
   *
   * @todo This should be removed rdf terms have their own
   * proper implementation.
   */
  public function getWeight() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());
    $published_graph = $storage->getGraphHandler()->getBundleGraphUri($this->getEntityTypeId(), $this->bundle(), SparqlGraphInterface::DEFAULT);
    $entity_graph_name = $this->get('graph')->target_id;
    // If no graph is yet set, get the default graph for the entity.
    if (empty($entity_graph_name)) {
      $entity_graph_name = $storage->getGraphHandler()->getDefaultGraphId($this->getEntityTypeId());
    }
    $entity_graph = $storage->getGraphHandler()->getBundleGraphUri($this->getEntityTypeId(), $this->bundle(), $entity_graph_name);
    return ($entity_graph === $published_graph);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    // @todo Implement setPublished() method.
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    // @todo Implement setUnpublished() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->hasUidMapping() ? $this->get('uid')->entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->hasUidMapping() ? $this->getEntityKey('uid') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    if ($this->hasUidMapping()) {
      $this->set('uid', $uid);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    if ($this->hasUidMapping()) {
      $this->set('uid', $account->id());
    }
    return $this;
  }

  /**
   * Returns whether the bundle of the entity has a mapping for the uid key.
   *
   * @return bool
   *   Whether the entity bundle has a value for the uid key mapping.
   */
  protected function hasUidMapping() {
    return $this->hasFieldMapping('uid', 'target_id');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFromGraph(string $graph_id): void {
    if (!$this->isNew()) {
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage($this->entityTypeId);
      $storage->deleteFromGraph([$this], $graph_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    if ($this->isNew()) {
      return [];
    }
    return [$this->entityTypeId . ':' . md5($this->id())];
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function hasGraph($graph) {
    if ($this->isNew()) {
      return FALSE;
    }
    return $this->entityTypeManager()->getStorage($this->getEntityTypeId())->hasGraph($this, $graph);
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id, ?array $graph_ids = NULL) {
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass(get_called_class()));
    return $storage->load($id, $graph_ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(?array $ids = NULL, ?array $graph_ids = NULL) {
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass(get_called_class()));
    return $storage->loadMultiple($ids, $graph_ids);
  }

  /**
   * Returns whether the entity bundle has mapping for a certain field column.
   *
   * @param string $field_name
   *   The field name.
   * @param string $column
   *   The field column. Defaults to 'value'.
   *
   * @return bool
   *   TRUE if the mapping is set, FALSE otherwise.
   */
  protected function hasFieldMapping($field_name, $column = 'value') {
    $mapping = SparqlMapping::loadByName($this->getEntityTypeId(), $this->bundle());
    return $mapping && !empty($mapping->getFieldColumnMappingPredicate($field_name, $column));
  }

}
