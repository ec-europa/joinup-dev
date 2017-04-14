<?php

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
   */
  public function repositories() {
    /** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $entity_storage */
    $entity_storage = \Drupal::service('entity.manager')->getStorage('rdf_entity');
    $query = $entity_storage->getQuery()
      ->sort('id')
      ->condition('rid', 'collection')
      ->pager(10);

    $rids = $query->execute();
    $entities = $entity_storage->loadMultiple($rids);
    $list = ['#theme' => 'item_list'];
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    foreach ($entities as $entity) {
      $list['#items'][] = ['#markup' => $entity->link()];
    }

    // @todo Find out why paging is not working...
    $build = [
      'list' => $list,
    ];
    return $build;
  }

}
