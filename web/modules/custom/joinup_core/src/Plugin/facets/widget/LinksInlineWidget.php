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

}
