<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a read-only field item list class for the 'latest_release' field.
 */
class SolutionLatestReleasesFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    if ($this->getEntity()->id() && ($latest_release_id = $this->getLatestRelease())) {
      $this->list[0] = $this->createItem(0, ['target_id' => $latest_release_id]);
    }
  }

  /**
   * Returns a reference to solution's latest release.
   *
   * @return string|null
   *   A reference to solution's latest release
   */
  protected function getLatestRelease(): ?string {
    /** @var \Drupal\solution\Entity\SolutionInterface $solution */
    $solution = $this->getEntity();
    /** @var string $graph_id */
    $graph_id = $solution->get('graph')->target_id;

    $ids = \Drupal::entityQuery('rdf_entity')
      ->graphs([$graph_id])
      ->condition('rid', 'asset_release')
      ->condition('field_isr_is_version_of', $solution->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    return $ids ? key($ids) : NULL;
  }

}
