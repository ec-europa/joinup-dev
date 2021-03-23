<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of node type entities.
 *
 * @see \Drupal\node\Entity\NodeType
 */
class RdfTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Name');
    $header['description'] = [
      'data' => $this->t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No RDF bundles available. <a href=":link">Add RDF bundle</a>.', [
      ':link' => Url::fromRoute('entity.rdf_type.add_form')->toString(),
    ]);
    return $build;
  }

}
