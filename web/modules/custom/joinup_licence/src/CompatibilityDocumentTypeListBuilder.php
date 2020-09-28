<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of compatibility document type entities.
 *
 * @see \Drupal\joinup_licence\Entity\CompatibilityDocumentType
 */
class CompatibilityDocumentTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

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

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No compatibility document types available. <a href=":link">Add compatibility document type</a>.',
      [':link' => Url::fromRoute('entity.compatibility_document_type.add_form')->toString()]
    );

    return $build;
  }

}
