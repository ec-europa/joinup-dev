<?php

declare(strict_types = 1);

namespace Drupal\demo_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ImportSubscriber.
 */
class ImportSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $pathAliasStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Path\AliasStorageInterface $path_alias_storage
   *   The path alias storage service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasStorageInterface $path_alias_storage, FileSystemInterface $file_system) {
    $this->configFactory = $config_factory;
    $this->pathAliasStorage = $path_alias_storage;
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
        $this->pathAliasStorage->save('/legal/document/legal_notice', '/joinup/legal-notice', LanguageInterface::LANGCODE_NOT_SPECIFIED);
      }
    }
  }

}
