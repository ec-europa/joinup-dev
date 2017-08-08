<?php

/**
 * @file
 * Contains \ContactFormSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Behat step definitions for testing the contact form.
 */
class ContactFormSubContext extends DrupalSubContextBase {

  /**
   * {@inheritdoc}
   */
  public function cleanUsers() {
    try {
      parent::cleanUsers();
    }
    catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Navigates to the contact form.
   *
   * @When (I )go to the contact form
   * @When (I )visit the contact form
   * @When I am on the contact form
   */
  public function visitContactForm() {
    $this->visitPath('contact');
  }

}
