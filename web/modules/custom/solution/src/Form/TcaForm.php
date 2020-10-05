<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rdf_entity\RdfInterface;

/**
 * A simple page that presents a TCA form for the solution creation.
 */
class TcaForm extends FormBase {

  /**
   * The collection group from the route.
   *
   * @var \Drupal\collection\Entity\CollectionInterface
   */
  protected $collection;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solution_tca_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL) {
    $this->collection = $rdf_entity;

    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to create the Solution you need first check the field below and then press the <em>Yes</em> button to proceed.'),
    ];

    $form['solution_tca'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and accept <a href=":legal_notice_url">the legal notice</a> and I commit to manage my solution on a regular basis.', [
        ':legal_notice_url' => Url::fromRoute('entity.entity_legal_document.canonical', ['entity_legal_document' => 'legal_notice'], ['absolute' => TRUE])->toString(),
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
          ':input[name="solution_tca"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('solution_tca') === 0) {
      $form_state->setError($form['solution_tca'], 'You have to agree that you will manage your solution on a regular basis.');
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
  public function cancelSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.rdf_entity.canonical', [
      'rdf_entity' => $this->collection->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL): void {
    $form_state->setRedirect('solution.collection_solution.add', [
      'rdf_entity' => $this->collection->id(),
    ]);
  }

}
