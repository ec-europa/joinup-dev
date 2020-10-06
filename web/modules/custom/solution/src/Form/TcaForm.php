<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_group\TcaFormBase;
use Drupal\rdf_entity\RdfInterface;

/**
 * A simple page that presents a TCA form for the solution creation.
 */
class TcaForm extends TcaFormBase {

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
  protected function getEntityBundle(): string {
    return 'solution';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTcaBlockId(): string {
    return 'simple_block:solution_tca';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL): array {
    $this->collection = $rdf_entity;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state): void {
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
