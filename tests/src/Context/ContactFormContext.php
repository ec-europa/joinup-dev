<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat step definitions for testing the contact form.
 */
class ContactFormContext extends RawDrupalContext {

  /**
   * Navigates to the contact form.
   *
   * @When I go to the contact form
   * @When I visit the contact form
   * @When I am on the contact form
   */
  public function visitContactForm() {
    $this->visitPath('contact');
  }

}
