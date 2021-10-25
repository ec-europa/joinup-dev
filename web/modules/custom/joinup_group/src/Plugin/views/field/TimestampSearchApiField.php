<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\field;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\field\SearchApiStandard;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Date/time formatting of Search API entity_created and entity_changed fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("joinup_search_api_timestamp")
 */
class TimestampSearchApiField extends SearchApiStandard {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * The date/time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The date/time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions(): array {
    return [
      'date_format' => ['default' => 'small'],
      'custom_date_format' => ['default' => ''],
      'timezone' => ['default' => ''],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Note: 99% of this code has been stolen from Date::buildOptionsForm().
    // @see \Drupal\views\Plugin\views\field\Date::buildOptionsForm()
    $date_formats = [];
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', ['@name' => $value->label(), '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name)]);
    }

    $form['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats + [
        'custom' => $this->t('Custom'),
        'raw time ago' => $this->t('Time ago'),
        'time ago' => $this->t('Time ago (with "ago" appended)'),
        'raw time hence' => $this->t('Time hence'),
        'time hence' => $this->t('Time hence (with "hence" appended)'),
        'raw time span' => $this->t('Time span (future dates have "-" prepended)'),
        'inverse time span' => $this->t('Time span (past dates have "-" prepended)'),
        'time span' => $this->t('Time span (with "ago/hence" appended)'),
      ],
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'small',
    ];
    $form['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. Otherwise, enter the number of different time units to display, which defaults to 2.'),
      '#default_value' => isset($this->options['custom_date_format']) ? $this->options['custom_date_format'] : '',
    ];
    // Setup #states for all possible date_formats on the custom_date_format form element.
    foreach (['custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span'] as $custom_date_possible) {
      $form['custom_date_format']['#states']['visible'][] = [
        ':input[name="options[date_format]"]' => ['value' => $custom_date_possible],
      ];
    }
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#description' => $this->t('Timezone to be used for date output.'),
      '#options' => ['' => $this->t('- Default site/user timezone -')] + system_time_zones(FALSE, TRUE),
      '#default_value' => $this->options['timezone'],
    ];
    foreach (array_merge(['custom'], array_keys($date_formats)) as $timezone_date_formats) {
      $form['timezone']['#states']['visible'][] = [
        ':input[name="options[date_format]"]' => ['value' => $timezone_date_formats],
      ];
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    $value = $item['value'];

    // Code copied from \Drupal\views\Plugin\views\field\Date::render() with
    // some minor changes.
    // @see \Drupal\views\Plugin\views\field\Date::render()
    $format = $this->options['date_format'];
    if (in_array($format, ['custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span'])) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($value) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      // Will be positive for a datetime in the past (ago), and negative for a
      // datetime in the future (hence).
      $time_diff = $this->time->getRequestTime() - $value;
      switch ($format) {
        case 'raw time ago':
          return $this->dateFormatter->formatTimeDiffSince($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'time ago':
          return $this->t('%time ago', ['%time' => $this->dateFormatter->formatTimeDiffSince($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2])]);

        case 'raw time hence':
          return $this->dateFormatter->formatTimeDiffUntil($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'time hence':
          return $this->t('%time hence', ['%time' => $this->dateFormatter->formatTimeDiffUntil($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2])]);

        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . $this->dateFormatter->formatTimeDiffSince($value, ['strict' => FALSE, 'granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'inverse time span':
          return ($time_diff > 0 ? '-' : '') . $this->dateFormatter->formatTimeDiffSince($value, ['strict' => FALSE, 'granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'time span':
          $time = $this->dateFormatter->formatTimeDiffSince($value, ['strict' => FALSE, 'granularity' => is_numeric($custom_format) ? $custom_format : 2]);
          return ($time_diff < 0) ? $this->t('%time hence', ['%time' => $time]) : $this->t('%time ago', ['%time' => $time]);

        case 'custom':
          if ($custom_format == 'r') {
            return $this->dateFormatter->format($value, $format, $custom_format, $timezone, 'en');
          }
          return $this->dateFormatter->format($value, $format, $custom_format, $timezone);

        default:
          return $this->dateFormatter->format($value, $format, '', $timezone);
      }
    }

    return parent::render_item($count, $item);
  }

}
