<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\joinup_group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a formatter displaying a timestamp as a dynamic "time ago" string.
 *
 * The requirement is to render a "time ago" as output of this formatter but
 * because the value would change very often (every second), we will not be able
 * to cache it and, as an effect, the whole page will become uncacheable. For
 * this reason, we'll not return a "time ago" value from server. Instead we
 * display a formatted date/time. Then, in JS enabled browsers, we replace the
 * formatted date/time with a "time ago" string expression generated by the
 * timeago jquery plugin (http://timeago.yarp.com). The value rendered by the JS
 * plugin is refreshed each minute. This is degrading nice in browsers with JS
 * disabled where users will be able to see a nice formatted date/time.
 *
 * @see http://timeago.yarp.com
 *
 * @FieldFormatter(
 *   id = "joinup_timestamp_timeago",
 *   label = @Translation("Joinup Timestamp Timeago"),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class JoinupTimestampTimeagoFormatter extends TimestampFormatter {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new JoinupTimestampTimeagoFormatter.
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, TimeInterface $time) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $date_formatter, $date_format_storage);
    $this->time = $time;
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
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'tooltip_format' => 'long',
      'tooltip_format_custom' => '',
      'timeago_settings' => [
        'strings' => [
          'seconds' => 'few seconds',
        ],
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $date_formats = [];
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format($this->time->getRequestTime(), $machine_name),
      ]);
    }
    $date_formats['custom'] = $this->t('Custom');

    $form['tooltip_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Tooltip format'),
      '#description' => $this->t('Select the date/time format to be used for the tooltip.'),
      '#options' => $date_formats,
      '#default_value' => $this->getSetting('tooltip_format'),
    ];
    $form['tooltip_format_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tooltip custom format'),
      '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('tooltip_format_custom'),
      '#states' => [
        'visible' => [
          [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][tooltip_format]"]' => [
              'value' => 'custom',
            ],
          ],
        ],
      ],
    ];
    $form['timeago_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('A Yaml representation of settings to be passed to the timeago plugin'),
      '#desciption' => $this->t('Values passed here will be merged into the timeago plugin defaults. See <a href="http://timeago.yarp.com">timeago.yarp.com</a> for the settings structure.'),
      '#default_value' => Yaml::encode($this->getSetting('timeago_settings')),
      '#element_validate' => [[static::class, 'decodeTimeagoSettings']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $date_format = $this->getSetting('date_format');
    $tooltip_format = $this->getSetting('tooltip_format');
    $custom_date_format = $tooltip_format_custom = '';
    $timezone = $this->getSetting('timezone') ?: NULL;
    $langcode = $tooltip_langcode = NULL;

    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format')) === 'r') {
      $langcode = 'en';
    }
    if ($tooltip_format === 'custom' && ($tooltip_format_custom = $this->getSetting('tooltip_format_custom')) === 'r') {
      $tooltip_langcode = 'en';
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();

    $tags = [];
    // If the entity is a OG group, add the cache tag of its group content.
    if ($entity instanceof GroupInterface) {
      // Add all the node group content once.
      $tags = Cache::mergeTags($tags, Cache::buildTags('og-group-content', $entity->getCacheTagsToInvalidate()));
    }

    return [
      [
        '#theme' => 'time',
        '#attributes' => [
          // This attribute is used by the jquery plugin to get the time.
          'datetime' => $this->dateFormatter->format($items[0]->value, 'html_datetime', $timezone, $langcode),
          'class' => [
            'js-joinup-timestamp-timeago',
          ],
          // Show a tooltip on mouse hover so the user can read the exact date.
          'title' => $this->dateFormatter->format($items[0]->value, $tooltip_format, $tooltip_format_custom, $timezone, $tooltip_langcode),
        ],
        // Non-JS browsers will display this formatted date/time but most of the
        // browsers will replace this inner text with a "time ago" string
        // generated by the timeago jquery plugin (http://timeago.yarp.com).
        // @see web/modules/custom/joinup_core/js/timestamp_timeago.js
        // @see http://timeago.yarp.com.
        '#text' => $this->dateFormatter->format($items[0]->value, $date_format, $custom_date_format, $timezone, $langcode),
      ],
      '#attached' => [
        'library' => [
          'joinup_core/timeago',
          'joinup_core/timestamp_timeago',
        ],
        'drupalSettings' => [
          'joinupCore' => [
            // Site builders are able to configure the plugin settings.
            'timeagoSettings' => $this->getSetting('timeago_settings'),
          ],
        ],
      ],
      '#cache' => [
        'tags' => $tags,
        'contexts' => [
          'timezone',
        ],
      ],
    ];
  }

  /**
   * Provides an element validation callback for 'timeago_settings'.
   *
   * We use this element validation callback to decode the timeago settings Yaml
   * in order to store it as an array.
   *
   * @param array $element
   *   The element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The full form render array.
   */
  public static function decodeTimeagoSettings(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $timeago_settings = [];
    if ($yaml = trim($form_state->getValue($element['#parents']))) {
      try {
        $timeago_settings = Yaml::decode($yaml) ?: [];
      }
      catch (\Exception $exception) {
        // Nothing to do.
      }
    }
    $form_state->setValue($element['#parents'], $timeago_settings);
  }

}
