<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sparql_entity_storage\SparqlGraphInterface;

/**
 * Toggles an SPARQL graph to enabled or disabled.
 */
class SparqlGraphToggle extends ControllerBase {

  /**
   * Checks if the current user is able to toggle the SPARQL graph status.
   *
   * @param \Drupal\sparql_entity_storage\SparqlGraphInterface $sparql_graph
   *   The $sparql_graph entity.
   * @param string $toggle_operation
   *   The operation: 'enable', 'disable'.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(SparqlGraphInterface $sparql_graph, string $toggle_operation): AccessResultInterface {
    $forbidden =
      // The operation is 'enable' and the entity is already enabled.
      ($toggle_operation === 'enable' && $sparql_graph->status()) ||
      // The operation is 'disable' and the entity is already disabled.
      ($toggle_operation === 'disable' && !$sparql_graph->status()) ||
      // This is the 'default' SPARQL graph.
      ($sparql_graph->id() === SparqlGraphInterface::DEFAULT);

    return $forbidden ? AccessResult::forbidden() : AccessResult::allowed();
  }

  /**
   * Toggles the SPARQL graph status.
   *
   * @param \Drupal\sparql_entity_storage\SparqlGraphInterface $sparql_graph
   *   The $sparql_graph entity.
   * @param string $toggle_operation
   *   The operation: 'enable', 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures on entity save.
   */
  public function toggle(SparqlGraphInterface $sparql_graph, string $toggle_operation) {
    $arguments = [
      '%name' => $sparql_graph->label(),
      '%id' => $sparql_graph->id(),
    ];

    if ($toggle_operation === 'enable') {
      $sparql_graph->enable()->save();
      $message = $this->t("The %name (%id) graph has been enabled.", $arguments);
    }
    else {
      $sparql_graph->disable()->save();
      $message = $this->t("The %name (%id) graph has been disabled.", $arguments);
    }
    $this->messenger()->addStatus($message);

    return $this->redirect('entity.sparql_graph.collection');
  }

}
