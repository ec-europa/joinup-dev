<?php

/**
 * @file
 * Contains \Drupal\collection\Entity\Collection.
 */

namespace Drupal\collection\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Collection entities.
 */
class CollectionViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['collection']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Collection'),
      'help' => $this->t('The Collection ID.'),
    );

    return $data;
  }

}
