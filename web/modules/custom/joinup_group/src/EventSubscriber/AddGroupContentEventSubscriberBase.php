<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\joinup_group\Event\AddGroupContentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class for AddGroupContentEvent::BUILD_BLOCK event subscribers.
 */
abstract class AddGroupContentEventSubscriberBase implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AddGroupContentEvent::BUILD_BLOCK => ['addLinks', static::getPriority()],
    ];
  }

  /**
   * Adds a link to group add content menu.
   *
   * @param \Drupal\joinup_group\Event\AddGroupContentEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function addLinks(AddGroupContentEvent $event): void {
    foreach ($this->getBundles() as $entity_type_id => $bundle_ids) {
      $bundle_entity_type_id = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();
      /** @var \Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsInterface[] $bundles */
      $bundles = $this->entityTypeManager->getStorage($bundle_entity_type_id)->loadMultiple($bundle_ids);
      foreach ($bundles as $bundle_id => $bundle) {
        $route_parameters = $this->getRouteParameters($event, $entity_type_id, $bundle_id);
        $this->addOneLink($bundle->getSingularLabel(), $route_parameters, $event);
      }
    }
  }

  /**
   * Add a single link.
   *
   * @param string $label
   *   The link label.
   * @param array $route_parameters
   *   The link URL route parameters.
   * @param \Drupal\joinup_group\Event\AddGroupContentEvent $event
   *   The event.
   */
  protected function addOneLink(string $label, array $route_parameters, AddGroupContentEvent $event): void {
    $page_url = Url::fromRoute($this->getRouteName(), $route_parameters);
    if ($page_url->access()) {
      $event->addItem([
        '#type' => 'link',
        '#title' => $this->t('Add @label', ['@label' => $label]),
        '#url' => $page_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ]);
    }
  }

  /**
   * Returns a list of bundle IDs keyed by entity type ID.
   *
   * @return array[]
   *   The a list of bundle IDs keyed by entity type ID.
   */
  abstract protected function getBundles(): array;

  /**
   * Returns the name of the route used to add conetnt.
   *
   * @return string
   *   Thr route name.
   */
  abstract protected function getRouteName(): string;

  /**
   * Returns the route parameters needed to build the route.
   *
   * @param \Drupal\joinup_group\Event\AddGroupContentEvent $event
   *   The event.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return array
   *   Route parameters.
   */
  protected function getRouteParameters(AddGroupContentEvent $event, string $entity_type_id, string $bundle_id): array {
    return [];
  }

  /**
   * Gets the the subscriber priority.
   *
   * @return int
   *   The priority.
   */
  protected static function getPriority(): int {
    return 0;
  }

}
