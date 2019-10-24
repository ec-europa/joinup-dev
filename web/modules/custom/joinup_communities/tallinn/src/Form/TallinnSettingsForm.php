<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administrative form for 'tallinn' module.
 */
class TallinnSettingsForm extends FormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Creates a new form object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tallinn.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    return [
      'access_policy' => [
        '#type' => 'radios',
        '#title' => $this->t('Access to Tallinn Ministerial Declaration data'),
        '#options' => [
          'public' => $this->t('Public'),
          'collection' => $this->t('Collection (moderators and Tallinn collection members)'),
          'restricted' => $this->t('Restricted (moderators and Tallinn collection facilitators)'),
        ],
        '#default_value' => $this->state->get('tallinn.access_policy', 'restricted'),
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
    $this->state->set('tallinn.access_policy', $form_state->getValue('access_policy'));
    $this->messenger()->addStatus($this->t('Access policy successfully updated.'));
  }

}
