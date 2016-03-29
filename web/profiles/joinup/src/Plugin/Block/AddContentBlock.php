<?php

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *  id = "add_content_block",
 *  admin_label = @Translation("Add content"),
 * )
 */
class AddContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The collection route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $collectionContext;

  /**
   * Constructs a AddContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $collection_context
   *   The collection context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $collection_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->collectionContext = $collection_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('collection.collection_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];

    // Add a link to propose a collection. This is visible for everyone, even
    // anonymous users.
    $links['collection'] = [
      '#title' => $this->t('Propose collection'),
      '#url' => Url::fromRoute('collection.propose_form'),
    ];

    // Retrieve the collection from the context service. This needs to be done
    // manually since this is an optional context. Core only supports required
    // contexts (the ones that are enabled through the visibility conditions in
    // the block configuration). This also means we have to take care of the
    // caching ourselves.
    /** @var \Drupal\Core\Plugin\Context\Context[] $collection_contexts */
    $collection_contexts = $this->collectionContext->getRuntimeContexts(['collection']);
    if ($collection_contexts['collection']->hasContextValue()) {
      $url = Url::fromRoute('custom_page.collection_custom_page.add', [
        'rdf_entity' => $collection_contexts['collection']->getContextValue()->sanitizedId(),
      ]);
      $links['custom_page'] = [
        '#type' => 'link',
        '#title' => $this->t('Add custom page'),
        '#url' => $url,
        '#attributes' => ['class' => ['button', 'button--small']],
        '#access' => $url->access(),
      ];
    }

    // Render the links as an unordered list, styled as buttons.
    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
    ];

    foreach ($links as $key => $link) {
      $link += [
        '#type' => 'link',
        '#attributes' => ['class' => ['button', 'button--small']],
      ];
      $build['#items'][$key] = $link;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $context = parent::getCacheContexts();
    // The 'Add custom page' link is only visible for certain roles on certain
    // collections. Normally cache contexts are added automatically but this
    // link depends on an optional context which we manage ourselves.
    return Cache::mergeContexts($context, ['user.roles', 'collection']);
  }

}
