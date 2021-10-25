<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_group\Plugin\views\BundleListTrait;
use Drupal\search_api\Plugin\views\field\SearchApiStandard;

/**
 * Allows to show the Search API 'entity_bundle' field as its label.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("joinup_search_api_entity_bundle")
 */
class EntityBundleSearchApiField extends SearchApiStandard {

  use BundleListTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions(): array {
    return [
      'show_as_label' => ['default' => FALSE],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['show_as_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show as bundle label instead of bundle ID'),
      '#default_value' => $this->options['show_as_label'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    if ($this->options['show_as_label']) {
      $item['value'] = $this->getBundleLabel($item['value']);
    }
    return parent::render_item($count, $item);
  }

}
