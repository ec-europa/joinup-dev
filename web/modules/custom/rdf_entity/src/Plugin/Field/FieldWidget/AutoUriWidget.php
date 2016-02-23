<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldWidget\UriWidget.
 */

namespace Drupal\rdf_entity\Plugin\Field\FieldWidget;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Field widget that auto generates a unique uri.
 *
 * @FieldWidget(
 *   id = "auto_uri",
 *   label = @Translation("Auto populated URI field"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class AutoUriWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'prefix' => 'http://example.org',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['prefix'] = array(
      '#type' => 'url',
      '#title' => $this->t('Prefix of URI field'),
      '#default_value' => $this->getSetting('prefix'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = $this->t('URI field prefix: @prefix', array('@prefix' => $this->getSetting('prefix')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'value',
    );
    // Existing Id.
    if (isset($items[$delta]->value)) {
      $element['value']['#value'] = $items[$delta]->value;
    }
    // Generate a new id.
    else {
      $prefix = $this->getSetting('prefix');
      if (empty($prefix)) {
        throw new Exception('Auto URI widget has is un-configured: Prefix not set.');
      }
      // If needed, append a trailing slash.
      if (substr($prefix, -1) != '/') {
        $prefix .= '/';
      }
      $uuid = new Php();
      $element['value']['#value'] = $prefix . $uuid->generate();
    }
    return $element;
  }

}
