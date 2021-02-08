<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for integration with the Matomo analytics platform.
 */
class JoinupStatsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['joinup_stats.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'joinup_stats_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['launch_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Launch date'),
      '#description' => $this->t('The date when the website was launched. This is used as the start date for the "All time" option.'),
      '#default_value' => $this->config('joinup_stats.settings')->get('launch_date'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->config('joinup_stats.settings')
      ->set('launch_date', $form_state->getValue('launch_date'))
      ->save();
  }

}
