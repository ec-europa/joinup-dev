<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\facets\widget;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "links_inline",
 *   label = @Translation("List of links with wrapper text"),
 *   description = @Translation("A widget that shows some of the results with prefix and suffix text"),
 * )
 */
class LinksInlineWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Default cache contexts.
   *
   * @var string[]
   */
  const DEFAULT_CACHE_CONTEXTS = ['url.path', 'url.query_args'];

  /**
   * The cache contexts manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheContextsManager $cache_contexts_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cacheContextsManager = $cache_contexts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_contexts_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'all_text' => 'All',
      'prefix_text' => '',
      'suffix_text' => '',
      'cache_contexts' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);
    $config = $this->getConfiguration();

    $form['all_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All text'),
      '#description' => $this->t('Shown for the facet reset link.'),
      '#default_value' => $config['all_text'],
      '#required' => TRUE,
    ];

    $form['prefix_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix text'),
      '#description' => $this->t('Shown at the left of the options widget.'),
      '#default_value' => $config['prefix_text'],
    ];

    $form['suffix_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix text'),
      '#description' => $this->t('Shown at the right of the options widget.'),
      '#default_value' => $config['suffix_text'],
    ];

    $form['cache_contexts'] = [
      '#type' => 'select',
      '#title' => $this->t('Additional cache contexts'),
      '#description' => $this->t("The widget automatically adds the <code>url.path</code> and <code>url.query_args</code> cache contexts to the facet. You can select additional cache contexts to be added."),
      '#options' => array_diff($this->cacheContextsManager->getAll(), static::DEFAULT_CACHE_CONTEXTS),
      '#multiple' => TRUE,
      '#default_value' => $config['cache_contexts'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    // Set the facet as it's done in the parent implementation, because the
    // build methods use it.
    $this->facet = $facet;

    $active = [];
    $inactive = [];
    foreach ($facet->getResults() as $result) {
      $item = empty($result->getUrl()) ? $this->buildResultItem($result) : $this->buildListItems($facet, $result);

      if ($result->isActive()) {
        $active[] = $item;
      }
      else {
        $inactive[] = $item;
      }
    }

    // When there is no active items, add the reset link to it, otherwise move
    // it to the end of all the inactive items.
    $all_link = $this->generateResetLink($facet);
    if (!empty($active)) {
      $inactive[] = $all_link;
    }
    // Do not add the link if there are no items at all.
    elseif (!empty($inactive)) {
      $active[] = $all_link;
    }

    $configuration = $this->getConfiguration();

    return [
      '#theme' => 'facet_widget_links_inline',
      '#items' => $inactive,
      '#active' => $active,
      '#prefix_text' => $configuration['prefix_text'],
      '#suffix_text' => $configuration['suffix_text'],
      '#attributes' => [
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-facet-alias' => $facet->getUrlAlias(),
      ],
      '#cache' => [
        'contexts' => Cache::mergeContexts($configuration['cache_contexts'], static::DEFAULT_CACHE_CONTEXTS),
      ],
    ];
  }

  /**
   * Generates a reset link for the facet.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being build.
   *
   * @return array
   *   The renderable array of the link.
   */
  protected function generateResetLink(FacetInterface $facet) {
    $request = \Drupal::service('request_stack')->getMasterRequest();
    /** @var \Symfony\Component\HttpFoundation\ParameterBag $get_params */
    $get_params = clone $request->query;
    if ($get_params->has('page')) {
      $get_params->remove('page');
    }

    if ($facet->getFacetSource()->getPath()) {
      $request = Request::create($facet->getFacetSource()->getPath());
    }
    $url = Url::createFromRequest($request);
    $url->setOption('attributes', ['rel' => 'nofollow']);

    // Retrieve the filter key from the url processor.
    // @see \Drupal\facets_range_widget\Plugin\facets\processor\RangeSliderProcessor::build()
    /** @var \Drupal\facets\Plugin\facets\processor\UrlProcessorHandler $url_processor_handler */
    $url_processor_handler = $facet->getProcessors()['url_processor_handler'];
    $url_processor = $url_processor_handler->getProcessor();
    $filter_key = $url_processor->getFilterKey();

    $filter_params = $get_params->get($filter_key, [], TRUE);
    foreach ($facet->getResults() as $result) {
      if ($result->isActive()) {
        $active_filter_string = $facet->getUrlAlias() . $url_processor->getSeparator() . $result->getRawValue();
        $filter_params = array_diff($filter_params, [$active_filter_string]);
      }
    }

    $get_params->set($filter_key, array_values($filter_params));
    // Add the get parameters when non-empty.
    if ($get_params->all() !== [$filter_key => []]) {
      $url->setOption('query', $get_params->all());
    }

    // Re-use the same markup as the other facet items. The reset link is never
    // active and it doesn't have any count.
    $text = [
      '#theme' => 'facets_result_item',
      '#is_active' => FALSE,
      '#value' => $this->getConfiguration()['all_text'],
      '#show_count' => FALSE,
      '#facet' => $facet,
    ];
    $link = new Link($text, $url);

    return $link->toRenderable();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildResultItem(ResultInterface $result) {
    $count = $result->getCount();
    return [
      '#theme' => 'facets_result_item',
      // Never render the activated indicator, as active facets are moved in the
      // related area.
      '#is_active' => FALSE,
      '#facet' => $result->getFacet(),
      '#value' => $result->getDisplayValue(),
      '#show_count' => $this->getConfiguration()['show_numbers'] && ($count !== NULL),
      '#count' => $count,
    ];
  }

}
