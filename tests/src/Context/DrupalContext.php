<?php

/**
 * @file
 * Contains \Drupal\joinup\Context\DrupalContext.
 */

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\DrupalContext as DrupalExtensionDrupalContext;

/**
 * Provides step definitions for interacting with Drupal.
 */
class DrupalContext extends DrupalExtensionDrupalContext {

  /**
   * {@inheritdoc}
   */
  public function loggedIn() {
    $session = $this->getSession();
    $session->visit($this->locatePath('/'));

    // Check if the 'logged-in' class is present on the page.
    $element = $session->getPage();
    return $element->find('css', 'body.user-logged-in');
  }

  /**
   * Assert that certain fields are present on the page.
   *
   * @param string $fields
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then /^(?:|the following )fields should be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsPresent($fields) {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    $fields = array_filter($fields);
    $not_found = [];
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', array('field', $field));
      if (!$is_found) {
        $not_found[] = $field;
      }
    }
    if ($not_found) {
      throw new \Exception("Field(s) expected, but not found: " . implode(', ', $not_found));
    }
  }

  /**
   * Assert that certain fields are not present on the page.
   *
   * @param string $fields
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then /^(?:|the following )fields should not be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsNotPresent($fields) {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    $fields = array_filter($fields);
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', array('field', $field));
      if ($is_found) {
        throw new \Exception("Field should not be found, but is present: " . $field);
      }
    }
  }
}
