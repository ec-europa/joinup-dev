<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default implementation of 'joinup_core.local_task_links_helper' service.
 */
class LocalTaskLinksHelper implements LocalTaskLinksHelperInterface {

  /**
   * The horizontal tabs block ID.
   *
   * @var string
   */
  protected const HORIZONTAL_TABS_BLOCK = 'horizontal_tabs';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_mype_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_mype_manager) {
    $this->entityTypeManager = $entity_mype_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function allowHorizontalTabs(): AccessResultInterface {
    return $this->entityTypeManager
      ->getStorage('block')
      ->load(static::HORIZONTAL_TABS_BLOCK)
      ->access('view', NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function allowThreeDotsMenu(): AccessResultInterface {
    $horizontal_tabs_access = $this->allowHorizontalTabs();
    return AccessResult::forbiddenIf($horizontal_tabs_access->isAllowed())
      ->addCacheableDependency($horizontal_tabs_access);
  }

}
