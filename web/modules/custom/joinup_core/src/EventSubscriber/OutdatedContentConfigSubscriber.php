<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to config save event.
 */
class OutdatedContentConfigSubscriber implements EventSubscriberInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::SAVE => 'onConfigSave'];
  }

  /**
   * Reacts on 'joinup_core.outdated_content_threshold' config save.
   *
   * After 'joinup_core.outdated_content_threshold' is saved, the field cache is
   * stale, so we need to rebuild the field definitions cache by accounting
   * changes in this config. See joinup_core_entity_bundle_field_info().
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config CRUD event.
   *
   * @see joinup_core_entity_bundle_field_info()
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() === 'joinup_core.outdated_content_threshold') {
      // Entity bundle base field definitions cache needs rebuild.
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }
  }

}
