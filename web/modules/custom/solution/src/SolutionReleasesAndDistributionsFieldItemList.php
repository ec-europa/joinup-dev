<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a field item list class for the 'releases_and_distributions' field.
 *
 * This computed field returns a list references to solution's releases and
 * standalone distributions. It's a read-only field.
 *
 * @todo This currently reuses the storage graph of the solution. This works
 *   only as intended for published solutions. In case the solution is not
 *   published, only the unpublished releases and distributions will be
 *   returned.
 *
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5736
 */
class SolutionReleasesAndDistributionsFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    if ($this->getEntity()->id()) {
      foreach ($this->getReleasesAndDistributions() as $delta => $id) {
        $this->list[$delta] = $this->createItem($delta, ['target_id' => $id]);
      }
    }
  }

  /**
   * Returns a list of solution's releases and standalone distributions as IDs.
   *
   * @return string[]
   *   A list of solution's releases and standalone distributions as IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the 'rdf_entity' entity type doesn't exist.
   */
  protected function getReleasesAndDistributions(): array {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $this->getEntity();
    $graph_id = $solution->get('graph')->target_id;
    /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface $query */
    $query = $storage->getQuery();

    // Retrieve all releases for this solution.
    $ids = array_values($query
      ->graphs([$graph_id])
      ->condition('rid', 'asset_release')
      ->condition('field_isr_is_version_of', $solution->id())
      ->execute());

    // Collect also standalone distributions.
    foreach ($solution->get('field_is_distribution') as $field_item) {
      $ids[] = $field_item->target_id;
    }

    return $ids;
  }

}
