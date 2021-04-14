<?php

namespace Drupal\sparql_serialization_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines a bundle entity.
 *
 * @ConfigEntityType(
 *   id = "simple_sparql_type_test",
 *   label = @Translation("Testing bundle entity"),
 *   config_prefix = "type",
 *   bundle_of = "simple_sparql_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *   },
 * )
 */
class SimpleSparqlTypeTest extends ConfigEntityBundleBase {

  /**
   * The entity ID.
   *
   * @var string
   *   The bundle ID.
   */
  protected $id;

  /**
   * The human readable name of the entity.
   *
   * @var string
   *    Human readable name
   */
  protected $name;

}
