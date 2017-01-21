<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base plugin for entity ID generator plugins.
 */
abstract class RdfEntityIdPluginBase extends PluginBase implements RdfEntityIdPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->configuration['entity'];
  }

}
