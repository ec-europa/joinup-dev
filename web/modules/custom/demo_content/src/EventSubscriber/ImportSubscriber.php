<?php

namespace Drupal\demo_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ImportSubscriber.
 *
 * @package Drupal\demo_content
 */
class ImportSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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

    foreach ($imported as $entity) {
      $id = $entity->id();
      if (isset($file_mapping[$id])) {
        foreach ($file_mapping[$id] as $field_name => $file_name) {
          if ($entity->get($field_name)) {
            $file_path = $directory . $file_name;
            if (is_file($file_path)) {
              if ($file_path = file_unmanaged_copy($file_path)) {
                $file = File::create(['uri' => $file_path]);
                $file->save();
                $entity->set($field_name, $file->id());
              };
            }
          }
        }
      }
      $entity->save();
    }
  }

}
