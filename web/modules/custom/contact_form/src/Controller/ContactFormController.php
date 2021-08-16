<?php

declare(strict_types = 1);

namespace Drupal\contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns the response for the contact form route.
 */
class ContactFormController extends ControllerBase {

  /**
   * Shows the contact form page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The render array containing the contact form.
   */
  public function contactPage(Request $request) {
    $values = ['template' => 'contact_form_submission'];

    // Prepopulate the category if it has been passed as a query argument.
    if ($request->query->has('category')) {
      $category = $request->query->get('category');
      if ($this->isValidCategory($category)) {
        $values['field_contact_category'] = $category;
      }
    }

    // Prepopulate the page URL if it was passed as a query argument.
    if ($request->query->has('uri')) {
      $uri = $request->query->get('uri');
      if ($this->isValidUri($uri)) {
        $values['field_contact_url']['uri'] = 'internal:/' . ltrim($uri, '/');
      }
    }

    // Prepopulate the subject if it was passed as a query argument.
    if ($request->query->has('subject')) {
      $values['field_contact_subject'] = $request->query->get('subject');
    }

    $contact_message = $this->entityTypeManager()->getStorage('message')->create($values);
    $form = $this->entityFormBuilder()->getForm($contact_message);

    $form['content_redirect'] = [
      '#type' => 'value',
      '#value' => $request->server->get('HTTP_REFERER'),
    ];

    return $form;
  }

  /**
   * Checks whether the given category is valid for the contact form.
   *
   * @param string $category
   *   The category key, e.g. 'report'.
   *
   * @return bool
   *   TRUE if the category is valid.
   */
  protected function isValidCategory($category) {
    $contact_message = $this->entityTypeManager()->getStorage('message')->create([
      'template' => 'contact_form_submission',
    ]);
    $entity_form = $this->entityFormBuilder()->getForm($contact_message);
    return array_key_exists($category, $entity_form['field_contact_category']['widget']['#options']);
  }

  /**
   * Checks whether the given URI is valid for the contact form.
   *
   * Invalid and external URIs are rejected.
   *
   * @param string $uri
   *   The URI, e.g. '/node/1'.
   *
   * @return bool
   *   TRUE if the URI is valid.
   */
  protected function isValidUri($uri) {
    try {
      $url = Url::fromUri('internal:/' . $uri);
      if (!$url->isExternal()) {
        return TRUE;
      }
    }
    catch (\InvalidArgumentException $e) {
      // The URI is invalid.
    }

    return FALSE;
  }

}
