<?php

namespace Drupal\joinup_core\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;
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
class LinksInlineWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'all_text' => 'All',
      'prefix_text' => '',
      'suffix_text' => '',
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
      '#default_value' => $config['all_text'] ?: 'All',
      '#required' => TRUE,
    ];

    $form['prefix_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix text'),
      '#description' => $this->t('Shown at the left of the options widget.'),
      '#default_value' => $config['prefix_text'] ?: '',
    ];

    $form['suffix_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix text'),
      '#description' => $this->t('Shown at the right of the options widget.'),
      '#default_value' => $config['suffix_text'] ?: '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $content = parent::build($facet);

    $all_link = $this->generateResetLink($facet);
    if (!empty($facet->getActiveItems())) {
      $content['#items'][] = $all_link;
    }
    else {
      array_unshift($content['#items'], $all_link);
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'data-drupal-facet-id' => $facet->id(),
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
      'children' => $content,
    ];
    $build['children']['#theme'] = 'item_list__links_inline';
    $build['children']['#prefix'] = '<span>' . $this->getConfiguration()['prefix_text'] . '</span>';
    $build['children']['#suffix'] = '<span>' . $this->getConfiguration()['suffix_text'] . '</span>';

    return $build;
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

    $link = new Link($this->getConfiguration()['all_text'], $url);

    return $link->toRenderable();
  }

}
