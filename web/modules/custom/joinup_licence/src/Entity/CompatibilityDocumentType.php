<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Compatibility Document Type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "compatibility_document_type",
 *   label = @Translation("Compatibility document type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\joinup_licence\Form\CompatibilityDocumentTypeForm",
 *       "edit" = "Drupal\joinup_licence\Form\CompatibilityDocumentTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\joinup_licence\CompatibilityDocumentTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer compatibility document types",
 *   bundle_of = "compatibility_document",
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/compatibility_document_types/add",
 *     "edit-form" = "/admin/structure/compatibility_document_types/manage/{compatibility_document_type}",
 *     "delete-form" = "/admin/structure/compatibility_document_types/manage/{compatibility_document_type}/delete",
 *     "collection" = "/admin/structure/compatibility_document_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   },
 * )
 */
class CompatibilityDocumentType extends ConfigEntityBundleBase implements CompatibilityDocumentTypeInterface {

  /**
   * The machine name of this compatibility document type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the compatibility document type.
   *
   * @var string
   */
  protected $label;

}
