<?php
/**
 * @file
 * Contains \Drupal\rdf_entity\Entity\ContentEntityExample.
 */

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

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
   * @var string $rid
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
   * Returns the entity id fit for linking.
   */
  public function sanitizedId() {
    return str_replace('/', '\\', $this->getEntityKey('id'));
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
  public function setChangedTime($time) {
    // @todo Implement this.
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
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
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The ID of the RDF entity.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

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

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // @todo This is the key to proper bundle fields..!
    // @todo Only invoked after cache clear.
    $fields = array();
    // If ($bundle == 'admssw_softwareproject') {.
    $fields['test'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Test bundle admssw_softwareproject'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);
    // }.
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
   * Gets an array of placeholders for this entity.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return array
   *   An array of URI placeholders.
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = [];

    if ($rel != 'collection') {
      // The entity ID is needed as a route parameter.
      // Here we do the actual escaping of outbound rdf entity id's.
      // @see \Drupal\rdf_entity\ParamConverter\RdfEntityConverter::convert
      $uri_route_parameters[$this->getEntityTypeId()] = str_replace('/', '\\', $this->id());
    }
    if ($rel === 'revision') {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = str_replace('/', '\\', $this->getRevisionId());;
    }
    return $uri_route_parameters;
  }

}
