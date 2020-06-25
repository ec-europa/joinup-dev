<?php

declare(strict_types = 1);

namespace Drupal\eif\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\eif\Plugin\OgGroupResolver\EifGroupResolver;

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

  /**
   * Checks if the user has access to the recommendations page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(): AccessResultInterface {
    $eif_solution = $this->entityTypeManager()->getStorage('rdf_entity')->load(EifGroupResolver::EIF_ID);
    return $this->entityTypeManager()->getAccessControlHandler('rdf_entity')->access($eif_solution, 'view', NULL, TRUE);
  }

}
