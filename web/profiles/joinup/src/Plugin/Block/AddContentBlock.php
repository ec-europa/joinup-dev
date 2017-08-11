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
 *   id = "add_content_block",
 *   admin_label = @Translation("Add content"),
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Organic group"))
 *   }
 * )
 */
class AddContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $asset_release_context
   *   The asset release route context service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $asset_release_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->assetReleaseContext = $asset_release_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('asset_release.asset_release_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];

    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextValue();
    $group_type = $group->bundle();
    $route_parameters = ['rdf_entity' => $group->id()];

    if ($group_type === 'collection') {
      // Add a link to add a custom page.
      $page_url = Url::fromRoute('custom_page.collection_custom_page.add', $route_parameters);
      if ($page_url->access()) {
        $links['custom_page'] = [
          '#type' => 'link',
          '#title' => $this->t('Add custom page'),
          '#url' => $page_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }

      $solution_url = Url::fromRoute('solution.collection_solution.add', $route_parameters);
      if ($solution_url->access()) {
        $links['solution'] = [
          '#type' => 'link',
          '#title' => $this->t('Add solution'),
          '#url' => $solution_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }
    }

    if ($group_type === 'solution') {
      $release_url = Url::fromRoute('asset_release.solution_asset_release.add', $route_parameters);

      if ($release_url->access()) {
        $links['asset_release'] = [
          '#type' => 'link',
          '#title' => $this->t('Add release'),
          '#url' => $release_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }

      $distribution_url = Url::fromRoute('asset_distribution.asset_release_asset_distribution.add', $route_parameters);
      if ($distribution_url->access()) {
        $links['asset_distribution'] = [
          '#type' => 'link',
          '#title' => $this->t('Add distribution'),
          '#url' => $distribution_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }
    }

    // 'Add news' link.
    $news_url = Url::fromRoute('joinup_news.rdf_entity_news.add', $route_parameters);
    if ($news_url->access()) {
      $links['news'] = [
        '#type' => 'link',
        '#title' => $this->t('Add news'),
        '#url' => $news_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
    }

    // 'Add discussion' link.
    $discussion_url = Url::fromRoute('joinup_discussion.rdf_entity_discussion.add', $route_parameters);
    if ($discussion_url->access()) {
      $links['discussion'] = [
        '#type' => 'link',
        '#title' => $this->t('Add discussion'),
        '#url' => $discussion_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
    }

    // 'Add document' link.
    $document_url = Url::fromRoute('joinup_document.rdf_entity_document.add', $route_parameters);
    if ($document_url->access()) {
      $links['document'] = [
        '#type' => 'link',
        '#title' => $this->t('Add document'),
        '#url' => $document_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
    }

    // 'Add event' link.
    $event_url = Url::fromRoute('joinup_event.rdf_entity_event.add', $route_parameters);
    if ($event_url->access()) {
      $links['event'] = [
        '#type' => 'link',
        '#title' => $this->t('Add event'),
        '#url' => $event_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
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
            '#attributes' => ['class' => ['circle-menu__link']],
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
    if (empty($links)) {
      return [];
    }

    // Render the links as an unordered list, styled as buttons.
    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
    ];

    foreach ($links as $key => $link) {
      $link += [
        '#type' => 'link',
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
      $build['#items'][$key] = $link;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Temporary workaround for wrong caching.
   * @see: https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3133
   */
  public function getCacheContexts() {
    $context = parent::getCacheContexts();
    // The links are only visible for certain roles on certain collections.
    // Normally cache contexts are added automatically but these links depend on
    // an optional context which we manage ourselves.
    return Cache::mergeContexts($context, [
      'asset_release',
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
