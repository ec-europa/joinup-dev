<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base form for a TCA submission.
 */
abstract class TcaFormBase extends FormBase {

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a TcaFormBase object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Returns the bundle of the type the user is trying to create.
   *
   * @return string
   *   The bundle in a human readable format.
   */
  abstract protected function getEntityBundle(): string;

  /**
   * The simple block ID to load.
   *
   * @return string
   *   The simple block ID including the "simple_block:" prefix.
   */
  abstract protected function getTcaBlockId(): string;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $block = $this->blockManager->createInstance($this->getTcaBlockId());
    if (!($block instanceof Broken)) {
      $form['tca'] = $block->build();
    }

    // Rename "Collection to Community".
    $bundle = $this->getEntityBundle();
    if ($this->getEntityBundle() == 'collection') {
      $bundle = 'community';
    }

    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to create the :bundle you need first check the field below and then press the <em>Yes</em> button to proceed.', [
        ':bundle' => ucfirst($bundle),
      ]),
    ];

    $form['tca_agreement'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and accept <a href=":legal_notice_url">the legal notice</a> and I commit to manage my :bundle on a regular basis.', [
        ':legal_notice_url' => Url::fromRoute('entity.entity_legal_document.canonical', ['entity_legal_document' => 'legal_notice'], ['absolute' => TRUE])->toString(),
        ':bundle' => $this->getEntityBundle(),
      ]),
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No thanks'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelSubmit'],
    ];

    $form['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
      '#states' => [
        'disabled' => [
          ':input[name="tca_agreement"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('tca_agreement') === 0) {
      // Rename "Collection to Community".
      $bundle = $this->getEntityBundle();
      if ($this->getEntityBundle() == 'collection') {
        $bundle = 'community';
      }
      $form_state->setError($form['tca_agreement'], "You have to agree that you will manage your {$bundle} on a regular basis.");
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Submit handler for the 'No thanks' button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  abstract public function cancelSubmit(array &$form, FormStateInterface $form_state): void;

}
