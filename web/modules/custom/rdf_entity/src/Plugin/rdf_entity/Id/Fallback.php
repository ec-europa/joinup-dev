<?php

namespace Drupal\rdf_entity\Plugin\rdf_entity\Id;

use Drupal\Component\Uuid\Php;
use Drupal\rdf_entity\RdfEntityIdPluginBase;

/**
 * Provides a fallback entity ID generator plugin.
 *
 * This plugin doesn't declare a 'bundle' annotation because it applies to all
 * RDF entity bundles lacking a specific plugin.
 *
 * @RdfEntityId(
 *   id = "fallback",
 *   label = @Translation("Default entity ID generator"),
 * )
 */
class Fallback extends RdfEntityIdPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    return 'http://placeHolder/' .  (new Php())->generate();
  }

}
