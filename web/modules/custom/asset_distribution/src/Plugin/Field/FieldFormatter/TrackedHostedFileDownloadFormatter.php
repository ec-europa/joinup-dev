<?php

namespace Drupal\asset_distribution\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\file_url\Entity\RemoteFile;
use Drupal\file_url\FileUrlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "tracked_hosted_file_download",
 *   label = @Translation("Tracked hosted file download"),
 *   field_types = {
 *     "file_url"
 *   }
 * )
 */
class TrackedHostedFileDownloadFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The file URL handler service.
   *
   * @var \Drupal\file_url\FileUrlHandler
   */
  protected $fileUrlHandler;

  /**
   * Constructs a new TrackedHostedFileDownloadFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\file_url\FileUrlHandler $file_url_handler
   *   The file URL handler service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FileUrlHandler $file_url_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->fileUrlHandler = $file_url_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('file_url.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => 'Download',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title to use for the link.'),
      '#default_value' => $this->getSetting('title'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Use "@title" as link title', ['@title' => $this->getSetting('title')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      /** @var \Drupal\file\FileInterface $file */
      // Do not render remote files.
      if ($file instanceof RemoteFile) {
        continue;
      }

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $this->getSetting('title'),
        '#url' => Url::fromUri(file_create_url($file->getFileUri())),
      ];

      // Pass field item attributes to the theme function.
      $item = $file->_referringItem;
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    if (!empty($elements)) {
      $elements['#attached']['library'][] = 'asset_distribution/download_tracking';
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
    $file_url_handler = $this->fileUrlHandler;
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
          $file = $file_url_handler::urlToFile($item->target_id);
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
