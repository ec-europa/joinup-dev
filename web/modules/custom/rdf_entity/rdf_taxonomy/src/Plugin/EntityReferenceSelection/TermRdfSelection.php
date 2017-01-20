<?php

namespace Drupal\rdf_taxonomy\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "term_rdf_selection",
 *   label = @Translation("Taxonomy RDF term selection (with groups)"),
 *   entity_types = {"taxonomy_term"},
 *   group = "term_rdf_selection",
 *   weight = 1
 * )
 */
class TermRdfSelection extends TermSelection {

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
