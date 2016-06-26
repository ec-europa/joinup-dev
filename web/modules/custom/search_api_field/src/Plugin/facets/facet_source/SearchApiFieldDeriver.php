<?php

/**
 * @file
 * Contains \Drupal\search_api_page\Plugin\facets\facet_source\SearchApiPageDeriver.
 */

namespace Drupal\search_api_field\Plugin\facets\facet_source;

use Drupal\Core\Plugin\PluginBase;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;
use Drupal\field\Entity\FieldConfig;


/**
 * Derives a facet source plugin definition for every search api page.
 *
 * The definition of this plugin happens in facet_source\SearchApiPage, in this
 * deriver class we're actually getting all possible pages and creating plugins
 * for each of them.
 *
 * @see \Drupal\search_api_page\Plugin\facets\facet_source\SearchApiPage
 */
class SearchApiFieldDeriver extends FacetSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {

      $map = \Drupal::entityManager()->getFieldMapByFieldType('search');
      $ids = [];
      foreach ($map as $type => $info) {
        foreach ($info as $name => $data) {
          foreach ($data['bundles'] as $bundle_name) {
            $ids[] = "$type.$bundle_name.$name";
          }
        }
      }

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      foreach (FieldConfig::loadMultiple($ids) as $id => $field_config) {
        // Add plugin derivatives, they have 'search_api_page' as a special key
        // in them, because of this, there needs to happen less explode() magic
        // in the plugin class.
        $plugin_derivatives[$id] = [
          'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $id,
          'label' => $this->t('Search api field: %label (%id)', [
            '%label' => $field_config->label(),
            '%id' => $id
          ]),
          'description' => $this->t('Provides a facet source.'),
          'search_api_field' => $id,
        ] + $base_plugin_definition;

        $sources[] = $this->t('Search api field: %label (%id)', [
          '%label' => $field_config->label(),
          '%id' => $id
        ]);
      }
      uasort($plugin_derivatives, array($this, 'compareDerivatives'));

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
