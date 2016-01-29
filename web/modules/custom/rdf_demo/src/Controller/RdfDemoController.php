<?php
/**
 * @file
 * Contains \Drupal\rdf_demo\Controller\RdfDemoController.
 */

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
      ->condition('?entity', 'rdf:type', '<http://www.w3.org/ns/adms#AssetRepository>')
      ->pager(10);

    $rids = $query->execute();
    $entities = $entity_storage->loadMultiple($rids);
    $list = array('#theme' => 'item_list');
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    foreach ($entities as $entity) {
      $list['#items'][] = array('#markup' => $entity->link());
    }

    // @todo Find out why paging is not working...
    $build = array(
      'list' => $list,
    );
    return $build;
  }

}
