<?php

namespace Drupal\asset_distribution\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
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
 * Plugin implementation of the 'tracked_hosted_file_download' formatter.
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
      'hosted_files_title' => 'Download',
      'show_remote_files' => FALSE,
      'remote_files_title' => 'External',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['hosted_files_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title to use for hosted files links.'),
      '#default_value' => $this->getSetting('hosted_files_title'),
    ];
    $elements['show_remote_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show remote files'),
      '#description' => $this->t('Render also the remote files. <em>Note:</em> remote files are not tracked.'),
      '#default_value' => $this->getSetting('show_remote_files'),
    ];
    $elements['remote_files_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title for remote files'),
      '#description' => $this->t('The title to use for remote files links.'),
      '#default_value' => $this->getSetting('remote_files_title'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Use "@title" as link title for local files', [
      '@title' => $this->getSetting('hosted_files_title'),
    ]);
    if ($this->getSetting('show_remote_files')) {
      $summary[] = $this->t('Use "@title" as link title for remote files', [
        '@title' => $this->getSetting('remote_files_title'),
      ]);
    }
    else {
      $summary[] = $this->t('Do not show remote files');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      /** @var \Drupal\file\FileInterface $file */
      if ($file instanceof RemoteFile) {
        // Render the remote files only when the formatter is configured to do
        // so.
        if ($this->getSetting('show_remote_files')) {
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $this->getSetting('remote_files_title'),
            '#url' => Url::fromUri($file->getFileUri()),
          ];
        }
      }
      else {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $this->getSetting('hosted_files_title'),
          '#url' => Url::fromUri(file_create_url($file->getFileUri())),
          '#attributes' => [
            'id' => Html::getUniqueId('tracked-file-download'),
            'class' => ['track-download'],
            'data-tracking' => Url::fromRoute('asset_distribution.track_download', [
              'file' => $file->id(),
            ])->toString(),
            // Force the download of the file in HTML5 compatible browsers.
            'download' => $file->getFilename(),
          ],
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
