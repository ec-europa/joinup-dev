<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administrative form for setting the dashboard data permissions.
 *
 * Even this is a form designed to update a config value, we're not using
 * ConfigFormBase because that form is subject to 'config_readonly' form
 * disabling.
 */
class DashboardPermissionsForm extends FormBase {

  /**
   * If the 'config_readonly' module is installed.
   *
   * @var bool
   */
  protected $moduleConfigReadonlyIsInstalled;

  /**
   * Creates a new form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->setConfigFactory($config_factory);
    $this->moduleConfigReadonlyIsInstalled = $module_handler->moduleExists('config_readonly');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tallinn.dashboard_permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    return [
      'access_type' => [
        '#type' => 'radios',
        '#title' => $this->t('Access to the dashboard data'),
        '#options' => [
          'public' => $this->t('Public'),
          'restricted' => $this->t('Restricted (only moderators and Tallinn collection facilitators)'),
        ],
        '#default_value' => $this->config('tallinn.settings')->get('dashboard.access_type'),
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Save configuration'),
          '#button_type' => 'primary',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->toggleConfigReadonly(FALSE);
    $this->configFactory->getEditable('tallinn.settings')
      ->set('dashboard.access_type', $form_state->getValue('access_type'))
      ->save();
    $this->toggleConfigReadonly(TRUE);
    $this->messenger()->addStatus($this->t('Permissions successfully updated.'));
  }

  /**
   * Sets the 'config_readonly' switch on or off.
   *
   * @param bool $enable
   *   If to enable or disable the read-only mode.
   */
  protected function toggleConfigReadonly(bool $enable): void {
    // Act only when 'config_readonly' module is enabled.
    if (!$this->moduleConfigReadonlyIsInstalled) {
      return;
    }

    // Toggle the 'config_readonly' switch.
    $settings = Settings::getAll();
    $settings['config_readonly'] = $enable;
    new Settings($settings);
  }

}
