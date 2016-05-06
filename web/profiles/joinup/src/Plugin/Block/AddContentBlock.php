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
   * The solution route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $solutionContext;

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
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $solution_context
   *   The solution context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $collection_context, ContextProviderInterface $solution_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->collectionContext = $collection_context;
    $this->solutionContext = $solution_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('collection.collection_route_context'),
      $container->get('solution.solution_route_context')
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
    if ($collection_contexts && $collection_contexts['collection']->hasContextValue()) {
      $page_url = Url::fromRoute('custom_page.collection_custom_page.add', [
        'rdf_entity' => $collection_contexts['collection']->getContextValue()
          ->id(),
      ]);
      $links['custom_page'] = [
        '#type' => 'link',
        '#title' => $this->t('Add custom page'),
        '#url' => $page_url,
        '#attributes' => ['class' => ['button', 'button--small']],
        '#access' => $page_url->access(),
      ];

      $solution_url = Url::fromRoute('solution.collection_solution.add', [
        'rdf_entity' => $collection_contexts['collection']->getContextValue()->id(),
      ]);
      $links['solution'] = [
        '#type' => 'link',
        '#title' => $this->t('Add solution'),
        '#url' => $solution_url,
        '#attributes' => ['class' => ['button', 'button--small']],
        '#access' => $solution_url->access(),
      ];
    }

    if (!empty($this->solutionContext)) {
      // Same as above for a button regarding the distributions.
      /** @var \Drupal\Core\Plugin\Context\Context[] $solution_contexts */
      $solution_contexts = $this->solutionContext->getRuntimeContexts(['solution']);
      if ($solution_contexts && $solution_contexts['solution']->hasContextValue()) {
        $distribution_url = Url::fromRoute('asset_distribution.solution_asset_distribution.add', [
          'rdf_entity' => $solution_contexts['solution']->getContextValue()->id(),
        ]);
        $links['asset_distribution'] = [
          '#title' => $this->t('Add distribution'),
          '#url' => $distribution_url,
          '#access' => $distribution_url->access(),
        ];
      }
    }

    $licence_url = Url::fromRoute('rdf_entity.rdf_add', [
      'rdf_type' => 'licence',
    ]);
    $licence_url_access = $licence_url->access();
    $links['licence'] = [
      '#title' => $this->t('Add Licence'),
      '#url' => $licence_url,
      '#access' => $licence_url->access(),
    ];

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
    // The links are only visible for certain roles on certain
    // collections. Normally cache contexts are added automatically but this
    // links depends on an optional context which we manage ourselves.
    return Cache::mergeContexts($context, [
      'user.roles',
      'collection',
      'solution',
    ]);
  }

}
