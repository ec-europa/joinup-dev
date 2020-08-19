<?php

declare(strict_types = 1);

namespace Drupal\eif\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form class for EIF recommendation selector.
 */
class EifRecommendationSelectorForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new form instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eif_recommendation_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tids = $storage->getQuery()
      ->condition('vid', 'eif_recommendation')
      ->execute();

    $options = [];
    foreach ($storage->loadMultiple($tids) as $tid => $term) {
      $options[$tid] = $this->t('Solutions implementing @recommendation', ['@recommendation' => $term->label()]);
    }
    natcasesort($options);

    $form['term'] = [
      '#type' => 'select',
      '#title' => $this->t('Jump to recommendation'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#attached' => [
        'library' => [
          'eif/recommendations.selector',
        ],
      ],
      '#attributes' => [
        'data-drupal-eif-recommendation-selector' => 'true',
      ],
      '#cache' => [
        'tags' => [
          'taxonomy_term_list:eif_recommendation',
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Jump'),
      '#attributes' => [
        'class' => [
          'js-hide',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('entity.taxonomy_term.canonical', [
      'taxonomy_term' => $form_state->getValue('term'),
    ]);
  }

}
