<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\QueueWorker;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\pathauto\PathautoGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a worker to consume the 'joinup_group:group_content_update' queue.
 *
 * @QueueWorker(
 *   id = "joinup_group:group_content_update",
 *   title = @Translation("Recreate the group content URL aliases"),
 *   cron = {
 *     "time" = 10,
 *   },
 * ),
 */
class JoinupGroupContentQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The pathauto alias generator service.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $aliasGenerator;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $alias_generator
   *   The pathauto alias generator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PathautoGeneratorInterface $alias_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasGenerator = $alias_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('pathauto.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $entity = $this->entityTypeManager
      ->getStorage($data['entity_type_id'])
      ->load($data['entity_id']);

    if (!$entity) {
      // The entity might have been deleted in the meantime.
      return;
    }

    Cache::invalidateTags($entity->getCacheTagsToInvalidate());

    // Regenerate the group content alias.
    $this->aliasGenerator->updateEntityAlias($entity, 'bulkupdate');
  }

}
