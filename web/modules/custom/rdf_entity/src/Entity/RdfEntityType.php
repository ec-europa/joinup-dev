<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\rdf_entity\RdfEntityTypeInterface;

/**
 * Defines the Rdf models.
 *
 * @ConfigEntityType(
 *   id = "rdf_type",
 *   label = @Translation("Rdf entity type"),
 *   handlers = {
 *     "list_builder" = "\Drupal\rdf_entity\Entity\Controller\RdfTypeListBuilder",
 *     "form" = {
 *       "default" = "\Drupal\rdf_entity\Form\RdfTypeForm",
 *       "delete" = "\Drupal\rdf_entity\Form\RdfTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer rdf",
 *   config_prefix = "rdfentity",
 *   bundle_of = "rdf_entity",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/rdf_type/manage/{rdf_type}/add",
 *     "delete-form" = "/admin/structure/rdf_type/manage/{rdf_type}/delete",
 *     "overview-form" = "/admin/structure/rdf_type/manage/{rdf_type}/overview",
 *     "edit-form" = "/admin/structure/rdf_type/manage/{rdf_type}",
 *     "collection" = "/admin/structure/rdf_type",
 *   },
 *   config_export = {
 *     "name",
 *     "rid",
 *     "description",
 *     "rdftype",
 *     "rdf_label",
 *   }
 * )
 */
class RdfEntityType extends ConfigEntityBundleBase implements RdfEntityTypeInterface {
  /**
   * The bundle type of RDF entity.
   *
   * @var string $rid
   *   The bundle.
   */
  protected $rid;

  /**
   * The human readable name of the entity.
   *
   * @var string $name
   *    Human readable name
   */
  protected $name;

  /**
   * A brief description of this rdf bundle.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->rid;
  }

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this rdf bundle.
   */
  public function getDescription() {
    return $this->description;
  }

}
