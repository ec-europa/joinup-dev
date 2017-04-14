<?php

namespace Drupal\joinup_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Language\LanguageInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'image_url_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_url_formatter",
 *   label = @Translation("Image URL formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageUrlFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    // This formatter doesn't support linking.
    unset($settings['image_link']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // This formatter doesn't support linking.
    unset($element['image_link']);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This method contains the same code as the parent class, except the image
   * link part.
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    return $this->viewElements($items, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      /** @var \Drupal\file\Entity\File $file */
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      // @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter::viewElements()
      // @todo is this needed?
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      // Get the uri to the file.
      $uri = $file->getFileUri();

      // If an image style is provided, apply it and retrieve the new uri.
      if (isset($image_style)) {
        $dimensions = [
          'width' => $item->width,
          'height' => $item->height,
        ];

        $image_style->transformDimensions($dimensions, $uri);
        $uri = $image_style->buildUrl($uri);
      }

      $elements[$delta] = [
        '#plain_text' => file_create_url($uri),
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }

    return $elements;
  }

}
