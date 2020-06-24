<?php

declare(strict_types = 1);

namespace Drupal\eif\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;

/**
 * Creates a list of EIF recommendations.
 */
class RecommendationsController extends ControllerBase {

  /**
   * Builds the page.
   *
   * @return array
   *   The render array.
   */
  public function build(): array {
    $vocabulary = $this->entityTypeManager()->getStorage('taxonomy_vocabulary')->load('eif_recommendations');
    $taxonomy_storage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_storage->loadTree($vocabulary->id(), 0, NULL, TRUE);

    $rows = [];
    $cache_tags = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($tree as $key => $term) {
      $rows[$key] = [
        'term' => $term->toLink($term->getName()),
      ];
      $cache_tags = Cache::mergeTags($cache_tags, $term->getCacheTags());
    }
    $cache_tags = Cache::mergeTags($cache_tags, $term->getEntityType()->getListCacheTags());

    return [
      '#type' => 'table',
      '#empty' => $this->t('No recommendations found.'),
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'taxonomy',
      ],
      '#cache' => [
        'tags' => array_unique($cache_tags),
      ],
    ];
  }

}
