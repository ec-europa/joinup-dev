<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_core\Form\ShareForm as OriginalForm;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to share a solution inside collections.
 */
class ShareForm extends OriginalForm {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The entity being shared.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL): array {
    return parent::doBuildForm($form, $form_state, $rdf_entity);
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function getTitle(RdfInterface $rdf_entity): TranslatableMarkup {
    return parent::buildTitle($rdf_entity);
  }

}
