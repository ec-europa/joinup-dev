<?php

namespace Drupal\file_url\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_url_default",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "file_url"
 *   }
 * )
 */
class FileUrlFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'mode' => 'link',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'link' => $this->t('Link (file and extension as link text)'),
        'plain' => $this->t('Plain URL'),
      ],
      '#default_value' => $this->getSetting('mode'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('mode')) {
      case 'link':
        $summary[] = $this->t('Link to file with file name and extension as text');
        break;

      case 'plain':
        $summary[] = $this->t('Plain URL');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      if ($this->getSetting('mode') === 'plain') {
        $elements['delta'] = [
          $elements[$delta] = [
            '#markup' => file_url_transform_relative(file_create_url($file->getFileUri())),
            '#cache' => [
              'tags' => $file->getCacheTags(),
            ],
          ],
        ];
      }
      else {
        $elements[$delta] = [
          '#theme' => 'file_link',
          '#file' => $file,
          '#description' => $item->description,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
      }

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }

      // Allow showing the full URI as tip.
      // @todo Probably the UX/UI team should decide if the full URL should be
      //   permanently displayed when showing distributions.
      $elements[$delta]['#attributes']['title'] = file_create_url($file->getFileUri());
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    /** @var \Drupal\file_url\FileUrlHandler $file_handler */
    $file_handler = \Drupal::service('file_url.handler');
    // Collect entity IDs to load. For performance, we want to use a single
    // "multiple entity load" to load all the entities for the multiple
    // "entity reference item lists" being displayed. We thus cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($this->needsEntityLoad($item)) {
          $file = $file_handler::urlToFile($item->target_id);
          $entities[$item->target_id] = $file;
        }
      }
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($entities[$item->target_id])) {
          $item->entity = $entities[$item->target_id];
          $item->_loaded = TRUE;
        }
        elseif ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

}
