<?php

declare(strict_types = 1);

namespace Drupal\rdf_demo\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Show a list of federated repositories.
 */
class RdfDemoController extends ControllerBase {

  /**
   * Returns a list of federated repositories.
   *
   * @return array
   *   A simple render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function repositories(): array {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorage $entity_storage */
    $entity_storage = $this->entityTypeManager()->getStorage('rdf_entity');
    $query = $entity_storage->getQuery()
      ->sort('id')
      ->condition('rid', 'collection')
      ->pager(10);

    $rids = $query->execute();
    $entities = $entity_storage->loadMultiple($rids);
    $list = ['#theme' => 'item_list'];
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    foreach ($entities as $entity) {
      $list['#items'][] = ['#markup' => $entity->toLink()->toString()];
    }

    // @todo Find out why paging is not working...
    $build = [
      'list' => $list,
    ];
    return $build;
  }

}
