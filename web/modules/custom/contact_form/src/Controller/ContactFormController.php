<?php

namespace Drupal\contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ContactFormController.
 *
 * @package Drupal\contact_form\Controller
 */
class ContactFormController extends ControllerBase {

  /**
   * Shows the contact form page.
   *
   * @return array
   *   The render array containing the contact form.
   */
  public function contactPage() {
    $contact_message = $this->entityTypeManager()->getStorage('message')->create([
      'template' => 'contact_form_submission',
    ]);

    $form = $this->entityFormBuilder()->getForm($contact_message);
    return $form;
  }

}
