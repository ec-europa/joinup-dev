<?php

/**
 * @file
 * Contains \Drupal\collection\CollectionListBuilder.
 */

namespace Drupal\collection;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Collection entities.
 *
 * @ingroup collection
 */
class CollectionListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Collection ID');
    $header['name'] = $this->t('Name');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\collection\Entity\Collection */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.collection.edit_form', array(
          'collection' => $entity->id(),
        )
      )
    );

    return $row + parent::buildRow($entity);
  }

}
