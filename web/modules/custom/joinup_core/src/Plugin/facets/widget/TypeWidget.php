<?php

namespace Drupal\joinup_core\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "type",
 *   label = @Translation("Render the type facet"),
 *   description = @Translation("A widget that shows some of the results as tabs"),
 * )
 */
class TypeWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['tabs' => 0] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $config = $this->getConfiguration();
    // @todo replace with a textfield with validation.
    $values = range(1, 5);
    $form['tabs'] = [
      '#type' => 'select',
      '#title' => $this->t('Tabs'),
      '#description' => $this->t('The number of items to show separately as tabs with icon.'),
      '#options' => array_combine($values, $values),
      '#default_value' => $config['tabs'] ?: 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $this->facet = $facet;
    $config = $this->getConfiguration();

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = array_values($facet->getResults());
    $big_icons = [];
    foreach (array_splice($results, 0, $config['tabs']) as $result) {
      // The first N elements need to be rendered as tab. Adding an object
      // property is easier than overriding all the methods of the class.
      $result->asTab = TRUE;
      $big_icons[] = $this->buildSingleResult($result);
    }

    $items = [];
    foreach ($results as $result) {
      $items[] = $this->buildSingleResult($result);
    }

    // Check if there is more facets.
    $is_more = FALSE;
    if (!empty($items)) {
      $is_more = TRUE;
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
      'big_icons' => [
        '#theme' => $is_more ? 'facets_type_wrapper__more' : 'facets_type_wrapper',
        '#items' => $big_icons,
      ],
      'others' => [
        '#theme' => 'item_list__facets',
        '#items' => $items,
      ],
    ];

    return $build;
  }

  /**
   * Builds a single result item to a renderable array.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result item.
   *
   * @return array
   *   The facet result item as a render array.
   */
  protected function buildSingleResult(ResultInterface $result) {
    if (empty($result->getUrl())) {
      return $this->buildResultItem($result);
    }
    else {
      return $this->buildListItems($this->facet, $result);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildResultItem(ResultInterface $result) {
    $count = $result->getCount();
    $theme = isset($result->asTab) ? 'facets_type_result_item' : 'facets_result_item';
    if ($result->isActive()) {
      $theme .= '__active';
    }

    return [
      '#theme' => $theme,
      '#value' => $result->getDisplayValue(),
      '#show_count' => $this->getConfiguration()['show_numbers'] && ($count !== NULL),
      '#count' => $count,
      '#type' => $result->getRawValue(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildListItems($facet, ResultInterface $result) {
    $classes = ['facet-item'];
    if ($children = $result->getChildren()) {
      $items = $this->prepareLink($result);

      $children_markup = [];
      foreach ($children as $child) {
        $children_markup[] = $this->buildChild($child);
      }

      $classes[] = 'expanded';
      $items['children'] = [$children_markup];

      if ($result->isActive()) {
        $items['#attributes'] = ['class' => ['active-trail']];
      }
    }
    else {
      $items = $this->prepareLink($result);

      if ($result->isActive()) {
        $items['#attributes'] = ['class' => ['is-active']];
      }
    }

    $items['#wrapper_attributes'] = ['class' => $classes];
    $items['#attributes']['data-drupal-facet-item-id'] = $this->facet->getUrlAlias() . '-' . $result->getRawValue();

    if (isset($result->asTab)) {
      $items['#attributes']['class'][] = 'tab--content-type';
    }

    return $items;
  }

}
