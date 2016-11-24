<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Rdf entity.
 *
 * The Rdf class defines methods and fields for the Rdf entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see RdfInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 *
 * The class also uses the EntityChangedTrait trait which allows it to record
 * timestamps of save operations.
 *
 * @ingroup rdf_entity
 *
 * @ContentEntityType(
 *   id = "rdf_entity",
 *   label = @Translation("Rdf entity"),
 *   handlers = {
 *     "storage" = "\Drupal\rdf_entity\Entity\RdfEntitySparqlStorage",
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
 *   list_cache_contexts = { "user" },
 *   base_table = null,
 *   admin_permission = "administer rdf_entity entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "rid",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "rdf_type",
 *   links = {
 *     "canonical" = "/rdf_entity/{rdf_entity}",
 *     "edit-form" = "/rdf_entity/{rdf_entity}/edit",
 *     "delete-form" = "/rdf_enity/{rdf_entity}/delete",
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
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    // @todo Implement :-)
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    // @todo Find out if all rdf entities have a changed date.
    // If so, we need to define this as a 'base field'.
    // For now, this date is a workaround.
    return '2014-05-19T17:03:00';
    // @todo Change return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // This is the RDF unique identifier (URI) used as an unique identifier of
    // the entity in triple store. Ideally, we have used this as ID but, because
    // the URI happens to exceed a Drupal accepted length, it will break in core
    // and in a lot of modules. For this reason we use this as triple store ID
    // but we compute a shorter hash key to be used as Drupal entity ID.
    $fields['uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('URI'));

    // This is the Drupal entity ID field. We compute this field as 'uri' hash.
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', 48);

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rdf Type'))
      ->setDescription(t('The Rdf type of this entity.'))
      ->setSetting('target_type', 'rdf_type');

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The UUID field is provided just to allow core or modules to retrieve RDF
    // entities by UUID. In fact UUID is computed with the same value as 'uri'.
    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setRevisionable(FALSE)
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setCustomStorage(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($include_computed = TRUE) {
    // TODO: Change the autogenerated stub.
    return parent::getFields($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->getUri();
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    // As the ID is NULL, reset also the UUID and the URI.
    $uuid_key = $this->getEntityType()->getKey('uuid');
    $duplicate->set($uuid_key, NULL);
    $duplicate->set('uri', NULL);
    return $duplicate;
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
  public function setName($name) {
    $this->set('name', $name);
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
    $storage = $this->entityManager()->getStorage($this->getEntityTypeId());
    $published_graph = $storage->getBundleGraphUri($this->bundle(), 'default');
    $entity_graph_name = $this->get('graph')->first()->getValue()['value'];
    $entity_graph = $storage->getBundleGraphUri($this->bundle(), $entity_graph_name);
    return ($entity_graph === $published_graph);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    // TODO: Implement setPublished() method.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFromGraph($graph) {
    if (!$this->isNew()) {
      $this->entityManager()->getStorage($this->entityTypeId)->deleteFromGraph($this->id(), $graph);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->get('uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    // Once set, the URI cannot be changed.
    $current_uri = $this->getUri();
    $is_different = $current_uri !== $uri;
    if ($current_uri && $is_different) {
      throw new \InvalidArgumentException('The URI of a RDF entity cannot be changed.');
    }

    if ($is_different) {
      $this->set('uri', $uri);
      // Sync the ID.
      $this->set('id', $this->getUriHash($uri));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUriHash() {
    if (!$uri = $this->getUri()) {
      throw new \RuntimeException('URI is not set yet.');
    }
    return Crypt::hashBase64($uri);
  }

}
