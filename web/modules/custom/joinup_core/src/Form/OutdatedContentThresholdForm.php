<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_core\Entity\OutdatedContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UI to edit the 'joinup_core.outdated_content_threshold' config.
 */
class OutdatedContentThresholdForm extends ConfigFormBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new form instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $bundle_info, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);
    $this->bundleInfo = $bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('joinup_core.outdated_content_threshold');

    $form['help'] = [
      '#markup' => $this->t('In order to show the outdated content notice on a type of content, select the checkbox and enter the number of years after such content is marked as outdated.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['config'] = [
      '#type' => 'table',
      '#header' => [
        'label' => $this->t('Type of content'),
        'enabled' => $this->t('Allow labeling as outdated?'),
        'threshold' => $this->t('Label after (years)'),
      ],
    ];

    foreach ($this->bundleInfo->getAllBundleInfo() as $entity_type_id => $bundles) {
      $entity_type_label = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
      foreach ($bundles as $bundle => $info) {
        $class = $info['class'] ?? NULL;
        if ($class && is_subclass_of($class, OutdatedContentInterface::class)) {
          $threshold = $config->get("{$entity_type_id}.{$bundle}");
          $form['config']["{$entity_type_id}:{$bundle}"] = [
            'label' => [
              '#markup' => $this->t('@bundle (@type)', [
                '@bundle' => $info['label'],
                '@type' => $entity_type_label,
              ]),
            ],
            'enabled' => [
              '#type' => 'checkbox',
              '#default_value' => !empty($threshold),
              '#title' => $this->t('Allow labeling as outdated?'),
              '#title_display' => 'invisible',
            ],
            'threshold' => [
              '#type' => 'number',
              '#title' => $this->t('Years'),
              '#title_display' => 'invisible',
              '#min' => 1,
              '#default_value' => $threshold,
              '#states' => [
                'enabled' => [
                  ":input[name='config[{$entity_type_id}:{$bundle}][enabled]']" => [
                    'checked' => TRUE,
                  ],
                ],
              ],
            ],
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory->getEditable('joinup_core.outdated_content_threshold');
    foreach ($form_state->getValue('config') as $row_id => $row) {
      [$entity_type_id, $bundle] = explode(':', $row_id, 2);
      $threshold = $row['enabled'] ? $row['threshold'] : NULL;
      $config->set("{$entity_type_id}.{$bundle}", $threshold);
    }
    $config->save();

    // Entity bundle base field definitions cache needs rebuild.
    // @see joinup_core_entity_bundle_field_info()
    $this->entityFieldManager->clearCachedFieldDefinitions();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['joinup_core.outdated_content_threshold'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'outdated_content_threshold';
  }

}
