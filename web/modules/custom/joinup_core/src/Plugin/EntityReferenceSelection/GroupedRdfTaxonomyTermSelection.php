<?php

namespace Drupal\joinup_core\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides a RDF taxonomy term selection (with groups).
 *
 * This is inspired from https://www.drupal.org/project/taxonomy_container but
 * is adapted to use string ID for the parent term.
 *
 * @EntityReferenceSelection(
 *   id = "grouped_rdf_taxonomy_term_selection",
 *   label = @Translation("Taxonomy RDF term selection (with groups)"),
 *   entity_types = {"taxonomy_term"},
 *   group = "grouped_rdf_taxonomy_term_selection",
 *   weight = 1
 * )
 */
class GroupedRdfTaxonomyTermSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];

    $bundles = $this->entityManager->getBundleInfo('taxonomy_term');
    $handler_settings = $this->configuration['handler_settings'];
    $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);

    foreach ($bundle_names as $bundle) {
      $terms = $this->entityManager
        ->getStorage('taxonomy_term')
        ->loadTree($bundle, 0, NULL, TRUE);
      if ($terms) {
        foreach ($terms as $term) {
          if ($term->parents[0] === '') {
            $parent_name = $term->label();
          }
          else {
            $options[$bundle][$parent_name][$term->id()] = str_repeat('-', $term->depth) . $term->label();
          }
        }
      }
    }

    return $options;
  }

}
