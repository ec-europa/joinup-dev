<?php

declare(strict_types = 1);

namespace Drupal\joinup\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\joinup\TcaFormBase;

/**
 * A simple page that presents a TCA form for the collection creation.
 */
class TcaForm extends TcaFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collection_tca_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'collection';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTcaBlockId(): string {
    return 'simple_block:collection_tca';
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect(Url::fromUri('internal:/collections')
      ->getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('rdf_entity.propose_form', [
      'rdf_type' => 'collection',
    ]);
  }

}
