<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\joinup_group\Event\AddGroupContentEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *   id = "add_content_block",
 *   admin_label = @Translation("Add content"),
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Organic group"))
 *   },
 * )
 */
class AddContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextValue();

    $event = new AddGroupContentEvent($group);
    $this->eventDispatcher->dispatch($event::BUILD_BLOCK, $event);
    $items = $event->getItems();

    return $items ? [$items] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // We need to invalidate the cache whenever the parent group changes since
    // the available options in the add content block depend on certain settings
    // of the parent collection, such as the workflow status and the content
    // creation option.
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextValue();
    return Cache::mergeTags(parent::getCacheTags(), $group->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $context = parent::getCacheContexts();
    // The links are only visible for certain roles on certain collections.
    // Normally cache contexts are added automatically but these links depend on
    // an optional context which we manage ourselves.
    return Cache::mergeContexts($context, [
      // We vary by the RDF entity type that is in the current context (asset
      // release, collection or solution) because the options shown in the menu
      // are different for each of these bundles.
      'og_group_context',
      // We vary by OG role since a non-member is not allowed to add content.
      'og_role',
      // We vary by user role since a moderator has the ability to add licenses.
      'user.roles',
    ]);
  }

}
