<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Joinup specific implementation of the SiteAlertListBuilder.
 *
 * Omits the "Start time" and "End time" columns from the table, which are not
 * used in Joinup.
 *
 * @see \joinup_core_entity_type_alter()
 */
class JoinupSiteAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'active' => [
        'data' => $this->t('Active'),
        'field' => 'active',
        'specifier' => 'active',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'label' => [
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'message' => [
        'data' => $this->t('Message'),
        'field' => 'message',
        'specifier' => 'message',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'active' => ($entity->getActive()) ? $this->t('Active') : $this->t('Not Active'),
      'label' => $entity->label(),
      'message' => check_markup($entity->get('message')->value, $entity->get('message')->format),
    ];
    return $row + parent::buildRow($entity);
  }

}
