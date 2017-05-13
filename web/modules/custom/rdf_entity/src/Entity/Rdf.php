<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\UserInterface;

/**
 * Defines the ContentEntityExample entity.
 *
 * @ingroup rdf_entity
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
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
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.rdf_entity.canonical'
 *
 * See routing file above for the corresponding implementation
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
      ->setLabel(t('ID'));

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rdf Type'))
      ->setDescription(t('The Rdf type of this entity.'))
      ->setSetting('target_type', 'rdf_type');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\rdf_entity\Entity\Rdf::getCurrentUserId')
      ->setTranslatable(TRUE)
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
      ->setSetting('max_length', 255)
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

    // The UUID field is provided just to allow core or modules to retrieve RDF
    // entities by UUID. In fact UUID is computed with the same value as ID.
    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setRevisionable(FALSE)
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setCustomStorage(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Language'))
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
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    // As the ID is NULL, reset also the UUID.
    $uuid_key = $this->getEntityType()->getKey('uuid');
    $duplicate->set($uuid_key, NULL);
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
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());
    $published_graph = $storage->getBundleGraphUri($this->bundle(), 'default');
    $entity_graph_name = $this->get('graph')->first()->getValue()['value'];
    $entity_graph = $storage->getBundleGraphUri($this->bundle(), $entity_graph_name);
    return ($entity_graph === $published_graph);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    // TODO: Implement setPublished() method.
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    // TODO: Implement setUnpublished() method.
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
    if (empty($this->bundle())) {
      return NULL;
    }
    $bundle = $this->entityTypeManager()->getStorage('rdf_type')->load($this->bundle());
    $mapping = rdf_entity_get_third_party_property($bundle, 'mapping', 'uid');
    return !empty($mapping['target_id']['predicate']);
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
    return $this->entityTypeManager()->getStorage($this->getEntityTypeId())->hasGraph($this, $graph);
  }

}
