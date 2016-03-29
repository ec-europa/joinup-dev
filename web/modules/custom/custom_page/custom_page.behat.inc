<?php

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Behat step definitions for testing custom pages.
 */
class CustomPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Checks if the given custom page belongs to the given collection.
   *
   * If there are multiple custom pages or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the custom page to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title doesn't exist.
   *
   * @Then the :collection collection has a custom page titled :title
   */
  public function assertOgMembership($title, $collection) {
    $collection = \CollectionSubContext::getCollectionByName($collection);

    $custom_pages = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    $custom_page = reset($custom_pages);

    if (empty($custom_page)) {
      throw new \Exception("The custom page titled '$title' was not found.");
    }

    foreach ($custom_page->get(\Drupal\og\OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The custom page '$title' is not associated with collection '{$collection->label()}'.");
  }

}
