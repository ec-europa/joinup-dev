<?php

namespace Drupal\joinup_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for integration with the Piwik analytics platform.
 *
 * This allows to control at which time intervals data about visit and download
 * counts are retrieved from the Piwik server.
 *
 * @see \Drupal\joinup_core\EventSubscriber\RefreshCachedPiwikDataEventSubscriber
 */
class PiwikIntegrationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['joinup_core.piwik_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_integration_settings';
  }

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new PiwikIntegrationSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configFactory);

    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('joinup_core.piwik_settings');

    $form['introduction'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('These settings control the period for which visits and download counts are retrieved from the Piwik analytics platform, and which type of metric to use.'),
    ];

    // Visit counts of community content are used to determine the results of
    // the 'Recommended content' block.
    $form['visit_counts'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Visit counts'),
      '#tree' => TRUE,
    ];
    foreach ($this->getBundlesHavingField('field_visit_count') as $entity_type_id => $bundles) {
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle) {
        $form['visit_counts'][$bundle] = [
          '#type' => 'fieldset',
          '#title' => $bundle_info[$bundle]['label'],
        ];
        $form['visit_counts'][$bundle]['period'] = [
          '#type' => 'select',
          '#title' => $this->t('Time period'),
          '#options' => $this->getTimePeriodOptions(),
          '#default_value' => $config->get("visit_counts.$bundle.period"),
        ];
        $form['visit_counts'][$bundle]['type'] = [
          '#type' => 'radios',
          '#title' => $this->t('Type'),
          '#options' => [
            'nb_visits' => $this->t('Visits'),
            'nb_hits' => $this->t('Hits'),
          ],
          '#default_value' => $config->get("visit_counts.$bundle.type"),
        ];
      }
    }
    $form['visit_counts']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Visit counts are used to determine which content is shown in the "Recommended content" block on the homepage. Shorter time periods will cause the recommended content to be more fresh and dynamic, while longer periods will cause them to be more stable.<br />The difference between "visits" and "hits" is that a "visit" is a special metric in Piwik which measures visitor engagement rather than simply counting the number of page requests. Repeated requests to the same page by the same user in a short time frame do not count as a "visit" but they do count as a "hit".'),
    ];

    // Download counts of distributions are shown on solutions.
    $form['download_counts'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Download counts'),
      '#tree' => TRUE,
    ];
    foreach ($this->getBundlesHavingField('field_download_count') as $entity_type_id => $bundles) {
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle) {
        $form['download_counts'][$bundle] = [
          '#type' => 'fieldset',
          '#title' => $bundle_info[$bundle]['label'],
        ];
        $form['download_counts'][$bundle]['period'] = [
          '#type' => 'select',
          '#title' => $this->t('Time period'),
          '#options' => $this->getTimePeriodOptions(),
          '#default_value' => $config->get("download_counts.$bundle.period"),
        ];
        $form['download_counts'][$bundle]['type'] = [
          '#type' => 'radios',
          '#title' => $this->t('Type'),
          '#options' => [
            'nb_uniq_visitors' => $this->t('Unique hits'),
            'nb_hits' => $this->t('Hits'),
          ],
          '#default_value' => $config->get("download_counts.$bundle.type"),
        ];
      }
    }
    $form['download_counts']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Download counts are for files and attachments. Typically the time period will be set to "All time" to get the complete download count since the site was launched.<br />The difference between "unique hits" and "hits" is that "unique hits" only count each download by a single visitor once. If a visitor downloads a file multiple times, it still only counts as 1 unique hit. This can be used to deter abuse by repeated downloads to artificially increase the download count.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns the names of the bundles that have a field with the given name.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @return array[]
   *   An array of bundle names that contain the field, keyed by entity type ID.
   */
  protected function getBundlesHavingField($field_name) {
    $bundles = [];

    $field_map = $this->entityFieldManager->getFieldMapByFieldType('cached_computed_integer');
    foreach ($field_map as $entity_type_id => $field_info) {
      if (!empty($field_info[$field_name]['bundles'])) {
        if (empty($bundles[$entity_type_id])) {
          $bundles[$entity_type_id] = $field_info[$field_name]['bundles'];
        }
        else {
          $bundles[$entity_type_id] += $field_info[$field_name]['bundles'];
        }
      }
    }

    return $bundles;
  }

  /**
   * Returns select options for the time period fields.
   *
   * @return array
   *   An array of select options.
   */
  protected function getTimePeriodOptions() {
    // How long is a month? Somewhere between 30 and 31 days. To get a better
    // answer we can say that an average month is 1/12th of a year. And a year
    // is the time needed for the earth to orbit the sun, which is a bit more
    // than 365 days. So an average month is 30 days, 10 hours, 30 minutes, and
    // 45 seconds.
    // @see http://www.wolframalpha.com/input/?i=earth+orbital+period
    $month_in_days = 365.25636 / 12;
    return [
      7 => $this->t('1 week'),
      14 => $this->t('2 weeks'),
      22 => $this->t('3 weeks'),
      (int) round($month_in_days) => $this->t('1 month'),
      (int) round(2 * $month_in_days) => $this->t('2 months'),
      (int) round(3 * $month_in_days) => $this->t('3 months'),
      (int) round(6 * $month_in_days) => $this->t('6 months'),
      0 => $this->t('All time'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Form options are returned as strings. Cast them to integers before
    // saving.
    $values = $form_state->getValues();
    foreach (['visit_counts', 'download_counts'] as $key) {
      $values[$key] = array_map(function ($settings) {
        $settings['period'] = (int) $settings['period'];
        return $settings;
      }, $values[$key]);
    }

    $this->config('joinup_core.piwik_settings')
      ->set('visit_counts', $values['visit_counts'])
      ->set('download_counts', $values['download_counts'])
      ->save();
  }

}
