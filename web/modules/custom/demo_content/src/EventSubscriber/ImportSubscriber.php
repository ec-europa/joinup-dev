<?php

declare(strict_types = 1);

namespace Drupal\demo_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entities being imported.
 */
class ImportSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an ImportSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['default_content.import'] = ['entitiesImport'];

    return $events;
  }

  /**
   * Called whenever the default_content.import event is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event element. Contains the entities and the module.
   */
  public function entitiesImport(Event $event) {
    $path_alias_storage = $this->entityTypeManager->getStorage('path_alias');
    $file_mapping = $this->configFactory->get('demo_content.settings')->get('file_mappings');
    $imported = $event->getImportedEntities();
    $directory = drupal_get_path('module', 'demo_content') . '/fixtures/files/';

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($imported as $uuid => $entity) {
      $id = $entity->id();
      if (isset($file_mapping[$id])) {
        foreach ($file_mapping[$id] as $field_name => $file_name) {
          if ($entity->get($field_name)) {
            $file_path = $directory . $file_name;
            if (is_file($file_path)) {
              if ($file_path = $this->fileSystem->copy($file_path, 'public://')) {
                $file = File::create(['uri' => $file_path]);
                $file->save();
                $entity->set($field_name, $file->id());
              };
            }
          }
        }
      }
      $entity->save();

      if ($uuid === 'c0bac256-c243-4440-bd31-b2b988375f5b') {
        $path_alias_storage->save('/legal/document/legal_notice', '/joinup/legal-notice', LanguageInterface::LANGCODE_NOT_SPECIFIED);
      }
    }
  }

}
