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
   * The asset release route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $assetReleaseContext;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $collection_context, ContextProviderInterface $solution_context, ContextProviderInterface $asset_release_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->collectionContext = $collection_context;
    $this->solutionContext = $solution_context;
    $this->assetReleaseContext = $asset_release_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('collection.collection_route_context'),
      $container->get('solution.solution_route_context'),
      $container->get('asset_release.asset_release_route_context')
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
    $collection_contexts = $this->collectionContext->getRuntimeContexts(['og']);
    if ($collection_contexts && $collection_contexts['og']->hasContextValue()) {
      $page_url = Url::fromRoute('custom_page.collection_custom_page.add', [
        'rdf_entity' => $collection_contexts['og']->getContextValue()->id(),
      ]);
      if ($page_url->access()) {
        $links['custom_page'] = [
          '#type' => 'link',
          '#title' => $this->t('Add custom page'),
          '#url' => $page_url,
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
      }

      $solution_url = Url::fromRoute('solution.collection_solution.add', [
        'rdf_entity' => $collection_contexts['og']->getContextValue()->id(),
      ]);
      if ($solution_url->access()) {
        $links['solution'] = [
          '#type' => 'link',
          '#title' => $this->t('Add solution'),
          '#url' => $solution_url,
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
      }
    }

    /** @var \Drupal\Core\Plugin\Context\Context[] $solution_contexts */
    $solution_contexts = $this->solutionContext->getRuntimeContexts(['solution']);
    if ($solution_contexts && $solution_contexts['solution']->hasContextValue()) {
      $release_url = Url::fromRoute('asset_release.solution_asset_release.add', [
        'rdf_entity' => $solution_contexts['solution']->getContextValue()->id(),
      ]);

      if ($release_url->access()) {
        $links['asset_release'] = [
          '#type' => 'link',
          '#title' => $this->t('Add release'),
          '#url' => $release_url,
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
      }
    }

    if ($collection_contexts && $collection_contexts['og']->hasContextValue()
      || $solution_contexts && $solution_contexts['solution']->hasContextValue()
    ) {
      $id = NULL;
      if ($collection_contexts['og']->hasContextValue()) {
        $id = $collection_contexts['og']->getContextValue()->id();
      }
      if ($solution_contexts['solution']->hasContextValue()) {
        $id = $solution_contexts['solution']->getContextValue()->id();
      }
      if ($id) {
        $news_url = Url::fromRoute('joinup_news.rdf_entity_news.add', [
          'rdf_entity' => $id,
        ]);
        if ($news_url->access()) {
          $links['news'] = [
            '#type' => 'link',
            '#title' => $this->t('Add news'),
            '#url' => $news_url,
            '#attributes' => ['class' => ['button', 'button--small']],
          ];
        }
      }
    }

    if (!empty($this->assetReleaseContext)) {
      /** @var \Drupal\Core\Plugin\Context\Context[] $asset_release_contexts */
      $asset_release_contexts = $this->assetReleaseContext->getRuntimeContexts(['asset_release']);
      if ($asset_release_contexts && $asset_release_contexts['asset_release']->hasContextValue()) {
        $distribution_url = Url::fromRoute('asset_distribution.asset_release_asset_distribution.add', [
          'rdf_entity' => $asset_release_contexts['asset_release']->getContextValue()->id(),
        ]);
        if ($distribution_url->access()) {
          $links['asset_distribution'] = [
            '#type' => 'link',
            '#title' => $this->t('Add distribution'),
            '#url' => $distribution_url,
            '#attributes' => ['class' => ['button', 'button--small']],
          ];
        }
      }
    }

    $licence_url = Url::fromRoute('joinup_licence.add');
    if ($licence_url->access()) {
      $links['licence'] = [
        '#title' => $this->t('Add licence'),
        '#url' => $licence_url,
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
    // The links are only visible for certain roles on certain collections.
    // Normally cache contexts are added automatically but these links depend on
    // an optional context which we manage ourselves.
    return Cache::mergeContexts($context, [
      // @todo Change 'user' to the OG Roles per group type cache context when
      //   this exists.
      // @see https://github.com/amitaibu/og/issues/219
      'user',
      'user.roles',
      'collection',
      'asset_release',
      'solution',
      'url.path',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // @todo: This is a temporary workaround for the lack of og cache
    // contexts/tags. Remove this when Og provides proper cache context and
    // instead add the proper context to the getCacheContexts method.
    // @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2628
    return Cache::mergeContexts(parent::getCacheTags(), ['user.roles']);
  }

}
