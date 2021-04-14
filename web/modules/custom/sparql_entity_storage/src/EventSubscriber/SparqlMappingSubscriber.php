<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a subscriber class that listens to sparql_mapping config save event.
 */
class SparqlMappingSubscriber implements EventSubscriberInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Builds a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info) {
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onSparqlMappingSave',
    ];
  }

  /**
   * Listens to sparql_entity_storage.mapping.* configurations save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config CRUD event.
   */
  public function onSparqlMappingSave(ConfigCrudEvent $event): void {
    // After saving the SPARQL entity storage mapping, the bundle info should be
    // processed again im order to account the mapping changes.
    // @see sparql_entity_storage_entity_bundle_info_alter()
    $this->bundleInfo->clearCachedBundles();
  }

}
