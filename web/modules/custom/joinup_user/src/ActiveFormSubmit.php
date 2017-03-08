<?php

namespace Drupal\joinup_user;

/**
 * Class ActiveFormSubmit.
 *
 * Keep track of the form id of the form being submitted.
 */
class ActiveFormSubmit {
  protected $formId = NULL;

  /**
   * Set the form_id being submitted.
   *
   * @param string $form_id
   *   The form id.
   */
  public function setFormId($form_id) {
    $this->formId = $form_id;
  }

  /**
   * Get the form_id being submitted.
   */
  public function getFormId() {
    return $this->formId;
  }

}
