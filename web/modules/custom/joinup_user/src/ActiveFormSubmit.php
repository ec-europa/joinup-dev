<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

/**
 * Keeps track of the form ID of the form being submitted.
 */
class ActiveFormSubmit {

  /**
   * The form ID being submitted.
   *
   * @var string
   */
  protected $formId = NULL;

  /**
   * Sets the form ID being submitted.
   *
   * @param string $form_id
   *   The form id.
   */
  public function setFormId(string $form_id): void {
    $this->formId = $form_id;
  }

  /**
   * Returns the form ID being submitted.
   *
   * @return string|null
   *   The form ID, or NULL if no form ID has been set.
   */
  public function getFormId(): ?string {
    return $this->formId;
  }

}
