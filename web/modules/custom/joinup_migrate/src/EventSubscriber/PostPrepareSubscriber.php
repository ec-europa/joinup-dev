<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\Core\Site\Settings;
use Drupal\joinup_migrate\MockFileSystem;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that acts after the 'prepare' migration has run.
 */
class PostPrepareSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_IMPORT => 'mockFileSystem'];
  }

  /**
   * Reacts after the 'prepare' migration has run.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function mockFileSystem(MigrateImportEvent $event) {
    if ($event->getMigration()->id() === 'prepare' && Settings::get('joinup_migrate.mock_filesystem', TRUE)) {
      MockFileSystem::createTestingFiles();
    }
  }

}
