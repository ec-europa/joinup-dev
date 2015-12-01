<?php
/**
 * @file
 * Defines the Rdf entity type: e.g. Asset, Interoperability Solution,...
 */

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
 *       "reset" = "Drupal\taxonomy\Form\VocabularyResetForm",
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
 *     "reset-form" = "/admin/structure/rdf_type/manage/{rdf_type}/reset",
 *     "overview-form" = "/admin/structure/rdf_type/manage/{rdf_type}/overview",
 *     "edit-form" = "/admin/structure/rdf_type/manage/{rdf_type}",
 *     "collection" = "/admin/structure/rdf_type",
 *   },
 *   config_export = {
 *     "name",
 *     "rid",
 *     "description",
 *     "rdftype",
 *   }
 * )
 */
class RdfEntityType extends ConfigEntityBundleBase implements RdfEntityTypeInterface {
  protected $rid;
  protected $name;

  /**
   * {@inheritdoc}
   */
  public function id() {

    return $this->rid;
  }

}
